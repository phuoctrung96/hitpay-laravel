<?php

namespace App\Http\Controllers\MigratedApi;

use App\Business;
use App\Business\PaymentProvider;
use App\Enumerations\CountryCode;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\User;
use Exception;
use HitPay\Stripe\Customer;
use HitPay\Stripe\OAuth as StripeOAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\ClientRepository;
use League\OAuth2\Server\AuthorizationServer;
use Stripe\Exception\OAuth\InvalidGrantException;

class StripeController extends Controller
{
    /**
     * Get authorize URL.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function redirect(Request $request)
    {
        $state = Str::random(32).'|'.md5(microtime());

        Cache::put('stripe_old_oauth_login:'.$state, $state, 20 * 60);

        return Response::json([
            'redirect' => StripeOAuth::newWithClientId(PaymentProviderEnum::STRIPE_SINGAPORE)
                ->getAuthorizeUrl(URL::route('migrated-api.stripe.callback'), $state,
                    $request->get('landing') !== 'register'),
        ]);
    }

    /**
     * Authenticate user.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Laravel\Passport\ClientRepository $clientRepository
     * @param \League\OAuth2\Server\AuthorizationServer $server
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     * @throws \League\OAuth2\Server\Exception\OAuthServerException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function authenticate(Request $request, ClientRepository $clientRepository, AuthorizationServer $server)
    {
        $fingerprint = $request->fingerprint();

        $data = $this->validate($request, [
            'state' => [
                'required',
                'string',
            ],
            'scope' => [
                'required',
                'in:read_write',
            ],
            'code' => [
                'required',
                'string',
            ],
        ]);

        $state = Cache::pull('stripe_old_oauth_login:'.$data['state']);

        if ($state !== $data['state']) {
            throw ValidationException::withMessages([
                'state' => 'The state is invalid or expired.',
            ]);
        }

        try {
            /**
             * @var $token \Stripe\StripeObject
             * @var $account \Stripe\Account
             */
            [
                $token,
                $account,
            ] = StripeOAuth::new(PaymentProviderEnum::STRIPE_SINGAPORE)->authorizeAccount($data['code']);
        } catch (InvalidGrantException $exception) {
            if ($exception->getStripeCode() === 'invalid_grant') {
                throw ValidationException::withMessages([
                    'code' => 'Invalid authorization code.',
                ]);
            }

            throw $exception;
        }

        Log::info(sprintf('[%s] A Stripe account login from country %s detected, ID: %s, Name: %s.', $fingerprint,
            $account->country, $account->id, ($account->display_name ?? $account->business_name)));

        if (strtolower($account->country) !== CountryCode::SINGAPORE) {
            App::abort(403, 'The country of the Stripe account is not supported.');
        }

        if (!$account->charges_enabled) {
            Log::info(sprintf('[%s] The authorized Stripe account isn\'t charges enabled.', $fingerprint));
        }

        $paymentProviders = PaymentProvider::where('payment_provider', PaymentProviderEnum::STRIPE_SINGAPORE)
            ->where('payment_provider_account_id', $account->id)->get();

        if ($paymentProviders->count() > 1) {
            App::abort(403, 'The Stripe account is tied to multiple business accounts, please contact us.');
        } elseif ($paymentProviders->count() === 0) {
            DB::beginTransaction();
            $user = new User;
            $user->email_login_enabled = false;
            $user->save();

            $business = new Business;

            $business->payment_provider = Customer::getStripePlatformByCountry('sg');

            if (!empty($account->business_name)) {
                $business->name = $account->business_name;
            } elseif (!empty($account->statement_descriptor)) {
                $business->name = $account->statement_descriptor;
            } elseif (!empty($account->display_name)) {
                $business->name = $account->display_name;
            } else {
                throw new \Exception("Business name not set because
                    `business_name`, `statement_descriptor` or `display_name` empty. Please check.");
            }

            $business->statement_description = $account->statement_descriptor ?? $business->name;
            $business->country = CountryCode::SINGAPORE;
            $business->can_pick_up = false;

            $customer = Customer::newByCountry('sg')->create('business_id:'.$business->id);

            $business->payment_provider_customer_id = $customer->id;

            try {
                $user->businessesOwned()->save($business);
            } catch (Exception $exception) {
                $customer->delete();

                throw $exception;
            }

            $accountData = $account->toArray();

            if (isset($accountData['country'])) {
                $accountData['country'] = strtolower($accountData['country']);
            }

            if (isset($accountData['support_address']['country'])) {
                $accountData['support_address']['country'] = strtolower($accountData['support_address']['country']);
            }

            $business->paymentProviders()->create([
                'payment_provider' => $business->payment_provider,
                'payment_provider_account_type' => 'standard',
                'payment_provider_account_id' => $account->id,
                'stripe_publishable_key' => $token->stripe_publishable_key,
                'token_type' => $token->token_type,
                'access_token' => $token->access_token,
                'refresh_token' => $token->refresh_token,
                'token_scopes' => $token->scope,
                'data' => $accountData,
            ]);

            DB::commit();
        } else {
            /**
             * @var \App\Business\PaymentProvider $paymentProvider
             */
            $paymentProvider = $paymentProviders->first();

            if (!$paymentProvider) {
                App::abort(403, 'The authorized Stripe account doesn\'t exist, please register again.');
            }

            $accountData = $account->toArray();

            // We want the country codes to be in lower case. We assumes that Stripe never change the structure of
            // the account object returned.

            if (isset($accountData['country'])) {
                $accountData['country'] = strtolower($accountData['country']);
            }

            if (isset($accountData['support_address']['country'])) {
                $accountData['support_address']['country'] = strtolower($accountData['support_address']['country']);
            }

            $paymentProvider->stripe_publishable_key = $token->stripe_publishable_key;
            $paymentProvider->token_type = $token->token_type;
            $paymentProvider->access_token = $token->access_token;
            $paymentProvider->refresh_token = $token->refresh_token;
            $paymentProvider->token_scopes = $token->scope;
            $paymentProvider->data = $accountData;

            DB::beginTransaction();

            // We save the latest information first no matter what's happened. Then we looks for business, and then the
            // owner. These saved data may help us in researching or big data in future, even it's failed.

            $paymentProvider->save();

            DB::commit();

            $business = $paymentProvider->business()->first();

            if (!$business) {
                Log::info(sprintf('[%s] The Stripe account doesn\'t tie to any business account. This must be a mistake'
                    .' when migration. Please check the database and fix the issue now.', $fingerprint));

                App::abort(403, 'The Stripe account doesn\'t tie to any business account, please contact us.');
            }

            $user = $business->owner()->first();

            if (!$user) {
                Log::info(sprintf('[%s] The business account doesn\'t tie to any user account.  This must be a mistake when'
                    .' migration. Please check the database and fix the issue now.', $fingerprint));

                App::abort(403, 'The business account doesn\'t tie to any user account, please contact us.');
            }

            $accountOwnedCount = $user->businessesOwned()->count();

            if ($accountOwnedCount > 1) {
                Log::info(sprintf('[%s] The user account is having %d business accounts. This must be a mistake when'
                    .' migration or admin has created new business account for them? Please check the database and fix the'
                    .' issue now.', $fingerprint, $accountOwnedCount));

                App::abort(403, 'The user account is having more than 1 business account, please contact us.');
            }
        }

        $passportToken = $user->createToken('Via Old Stripe Login');

        return Response::json([
            'token_type' => 'Bearer',
            'expires_in' => $passportToken->token->expires_at->diffInSeconds($passportToken->token->created_at),
            'access_token' => $passportToken->accessToken,
            'refresh_token' => null,
        ]);
    }
}
