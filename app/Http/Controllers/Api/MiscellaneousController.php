<?php

namespace App\Http\Controllers\Api;

use App\Business\PaymentProvider;
use App\Enumerations\Business\SupportedCurrencyCode;
use App\Enumerations\CountryCode;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Http\Controllers\Controller;
use App\User;
use HitPay\Stripe\OAuth as StripeOAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\OAuth\InvalidGrantException;

class MiscellaneousController extends Controller
{
    // todo login method

    // notification switch on and off
    //
    //
    // push notification (submit token) ---------------------- √
    // send notification
    //
    // 1. To check the login method for a business. ---------- √
    //
    // 5. To refund a charge                                   √ 1/2
    // 2. To send receipt for a charge                         √ 1/3
    // customer can view & export transaction                  √ 1/2
    // send receipt, add to customer if not exists, also record who has been sent
    //
    // NOTE: There are 2 more APIs missing in this module.
    // 1.	Generate payment link ------------ > has to update the closed at, same as create
    // 2.	Send payment link     ------------ > same as send receipt
    //
    //
    //
    // NOTE: The order module is still not yet cleaned up, will push to server later around 3pm.

    // NOTE: There are 3 more APIs missing in this module.
    // 1.	Add Image – add image after the product is created.             √
    // 2.	Remove Image – remove image after the product is created.       √
    // 3.	Send product link                                               √
    //
    // todo export sale details with date range, starts from to

    /**
     * @return \Illuminate\Http\JsonResponse
     * @throws \ReflectionException
     */
    public function currency()
    {
        foreach (SupportedCurrencyCode::listConstants() as $key => $value) {
            $minimumAmount = 100;

            if (SupportedCurrencyCode::isNormal($value)) {
                $minimumAmount = $minimumAmount / 100;
            }

            $currencies[$key] = [
                'code' => strtoupper($value),
                'name' => Lang::get('misc.currency.'.$value),
                'is_zero_decimal' => SupportedCurrencyCode::isZeroDecimal($value),
                'minimum_amount' => $minimumAmount,
            ];
        }

        $currencies = Collection::make($currencies);
        $currencies->sortBy('name');

        return Response::json($currencies);
    }

    /**
     * Try to get Stripe OAuth URL for migrated account.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function getStripeOAuthUrl(Request $request)
    {
        $data = $this->validate($request, [
            'email' => [
                'required',
                'email',
            ],
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user && $user->email_login_enabled || PaymentProvider::where('data', 'like', '%'.$data['email'].'%')->first()) {
            return Response::json([
                'status' => 'email_login_enabled',
            ]);
        }

        return Response::json([
            'status' => 'stripe_account_not_found',
        ]);
    }

    /**
     * Try to authenticate migrated user via Stripe OAuth.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Stripe\Exception\OAuth\InvalidGrantException
     * @throws \Stripe\Exception\OAuth\OAuthErrorException
     * @throws \Exception
     */
    public function doStripeOauthLogin(Request $request)
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

        $state = Cache::pull('stripe_oauth_login:'.$data['state']);

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

        $paymentProvider = PaymentProvider::where('payment_provider', PaymentProviderEnum::STRIPE_SINGAPORE)
            ->where('payment_provider_account_id', $account->id)->first();

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
        } elseif ($user->email_login_enabled) {
            Log::info(sprintf('[%s] The user account has already setup to use HitPay login but still reach here. Wonder'
                .' how do they bypassed? Is the "check" API not good enough or the mobile app doesn\'t implement'
                .' correctly?', $fingerprint));

            App::abort(403, 'The user account seems already setup to use HitPay login, please try HitPay login.');
        }

        $accountOwnedCount = $user->businessesOwned()->count();

        if ($accountOwnedCount > 1) {
            Log::info(sprintf('[%s] The user account is having %d business accounts. This must be a mistake when'
                .' migration or admin has created new business account for them? Please check the database and fix the'
                .' issue now.', $fingerprint, $accountOwnedCount));

            App::abort(403, 'The user account is having more than 1 business account, please contact us.');
        }

        $passportToken = $user->createToken('Via Stripe Login');

        return Response::json([
            'token_type' => 'Bearer',
            'expires_in' => $passportToken->token->expires_at->diffInSeconds($passportToken->token->created_at),
            'access_token' => $passportToken->accessToken,
            'refresh_token' => null,
        ]);
    }
}
