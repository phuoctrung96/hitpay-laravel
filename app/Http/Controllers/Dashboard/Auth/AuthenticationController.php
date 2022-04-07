<?php

namespace App\Http\Controllers\Dashboard\Auth;

use App\Business\PaymentProvider;
use App\Enumerations\CountryCode;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Http\Controllers\Controller;
use App\User;
use HitPay\Stripe\OAuth as StripeOAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\OAuth\InvalidGrantException;

class AuthenticationController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showEmailForm()
    {
        return Response::redirectToRoute('login');
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function searchAccount(Request $request)
    {
        $data = $this->validate($request, [
            'email' => [
                'required',
                'email',
            ],
        ]);

        if (User::where('email', $data['email'])->where('email_login_enabled', true)->first() === null
            && PaymentProvider::where('data', 'like', '%'.$data['email'].'%')->first()) {
            $state = Str::random(32).'|'.md5(microtime());

            Cache::put('stripe_oauth_login:'.$state, $state, 20 * 60);

            return Response::redirectTo(StripeOAuth::newWithClientId(PaymentProviderEnum::STRIPE_SINGAPORE)
                ->getAuthorizeUrl(URL::route('stripe.authenticate'), $state, true));
        }

        return Response::redirectToRoute('login', [
            'email' => $data['email'],
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Stripe\Exception\OAuth\InvalidGrantException
     * @throws \Stripe\Exception\OAuth\OAuthErrorException
     * @throws \Exception
     */
    public function authenticateStripeAccount(Request $request)
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
                'stripe' => 'The state is invalid or expired.',
            ])->redirectTo(URL::route('auth'));
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
                    'stripe' => 'Invalid authorization code.',
                ])->redirectTo(URL::route('auth'));
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

        if ($paymentProviders->count() === 0) {
            App::abort(403, 'The authorized Stripe account doesn\'t exist, please register again.');
        } elseif ($paymentProviders->count() > 1) {
            foreach ($paymentProviders as $provider) {
                if (isset($provider->data['email'])) {
                    $stripeAccountEmails[] = $provider->data['email'];
                }
            }

            return Response::view('dashboard.authentication.auth', [
                'existing_accounts' => $stripeAccountEmails ?? [],
            ]);
        } else {
            /** @var \App\Business\PaymentProvider $paymentProvider */
            $paymentProvider = $paymentProviders->first();
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

        $business = $paymentProvider->business;

        if (!$business) {
            Log::info(sprintf('[%s] The Stripe account doesn\'t tie to any business account. This must be a mistake'
                .' when migration. Please check the database and fix the issue now.', $fingerprint));

            App::abort(403, 'The Stripe account doesn\'t tie to any business account, please contact us.');
        }

        $user = $business->owner;

        if (!$user) {
            Log::info(sprintf('[%s] The business account doesn\'t tie to any user account.  This must be a mistake when'
                .' migration. Please check the database and fix the issue now.', $fingerprint));

            App::abort(403, 'The business account doesn\'t tie to any user account, please contact us.');
        } elseif ($user->email_login_enabled) {
            Log::info(sprintf('[%s] The user account has already setup to use HitPay login but still reach here. Wonder'
                .' how do they bypassed? Is the "check" API not good enough or the mobile app doesn\'t implement'
                .' correctly?', $fingerprint));

            return Response::redirectToRoute('login', [
                'email' => $user->email,
            ]);
        }

        $accountOwnedCount = $user->businessesOwned()->get();

        if ($accountOwnedCount->count() > 1) {
            Log::info(sprintf('[%s] The user account is having %d business accounts. This must be a mistake when'
                .' migration or admin has created new business account for them? Please check the database and fix the'
                .' issue now.', $fingerprint, $accountOwnedCount));

            App::abort(403, 'The user account is having more than 1 business account, please contact us.');
        }

        Auth::login($user);

        return Response::redirectToRoute('dashboard.user.welcome');
    }
}
