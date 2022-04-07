<?php

namespace App\Http\Controllers\Api\Business\Stripe;

use App\Business as BusinessModel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\PaymentProvider;
use App\Logics\Business\PaymentProviderRepository;
use HitPay\Stripe\OAuth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PaymentProviderController extends Controller
{
    /**
     * StripeController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Show the authorized Stripe account, or the OAuth authorize URL.
     *
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business\PaymentProvider|\Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showAccountOrUrl(BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        $provider = $business->paymentProviders()->where('payment_provider', $business->payment_provider)->first();

        if ($provider) {
            return new PaymentProvider($provider);
        }

        $state = Str::random(32).'|'.$business->id;

        Cache::put('stripe_oauth:'.$state, $business->getKey(), 20 * 60);

        return Response::json([
            'redirect_url' => OAuth::newWithClientId($business->payment_provider)
                ->getAuthorizeUrl(URL::route('api.v1.dumper.stripe'), $state),
        ]);
    }

    /**
     * Authorize Stripe account.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business\PaymentProvider
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function authorizeAccount(Request $request, BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        $provider = $business->paymentProviders()->where('payment_provider', $business->payment_provider)->first();

        if ($provider) {
            throw new AuthorizationException('Stripe is already set up as a payment provider of this account, if you'
                .' plan to authorize another account, please deauthorize this account before proceeding.');
        }

        $paymentProvider = PaymentProviderRepository::store($request, $business, $business->payment_provider);

        return new PaymentProvider($paymentProvider);
    }

    /**
     * Deauthorize Stripe account from our side.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function deauthorizeAccount(BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        $provider = $business->paymentProviders()->where('payment_provider', $business->payment_provider)->first();

        if ($provider) {
            PaymentProviderRepository::delete($provider);
        }

        return Response::json([], 204);
    }
}
