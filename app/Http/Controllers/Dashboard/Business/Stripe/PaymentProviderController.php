<?php

namespace App\Http\Controllers\Dashboard\Business\Stripe;

use App\Business;
use App\Enumerations\PaymentProvider;
use App\Enumerations\PaymentProviderAccountType;
use App\Http\Controllers\Controller;
use App\Jobs\SetCustomPricingFromPartner;
use App\Logics\Business\PaymentProviderRepository;
use App\Logics\BusinessRepository;
use HitPay\Stripe\OAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PaymentProviderController extends Controller
{
    /**
     * PaymentProviderController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function showHomepage(Business $business)
    {
        Gate::inspect('update', $business)->authorize();

        $provider = $business->paymentProviders()->where('payment_provider', $business->payment_provider)->first();

        if ($provider && $provider->payment_provider_account_type !== 'standard') {
            return Response::redirectToRoute(
                'dashboard.business.payment-provider.stripe.onboard-verification',
                [ 'business_id' => $business->getKey() ]
            );
        }

        return Response::view('dashboard.business.payment-providers.stripe', compact('business', 'provider'));
    }

    /**
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function doRedirection(Business $business)
    {
        Gate::inspect('update', $business)->authorize();

        $provider = $business->paymentProviders()->where('payment_provider', $business->payment_provider)->first();

        if ($provider) {
            return Response::redirectToRoute('dashboard.business.payment-provider.stripe.home', [
                $business->getKey(),
            ]);
        }

        $state = Str::random(32).'|'.$business->getKey();

        Cache::put('stripe_oauth:'.$state, $business->getKey(), 20 * 60);

        return Response::redirectTo(OAuth::newWithClientId($business->payment_provider)
            ->getAuthorizeUrl(URL::route('dashboard.stripe.payment-provider.callback'), $state, true));
    }

    /**
     * Authorize Stripe account.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business\PaymentProvider|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function authorizeAccount(Request $request, Business $business)
    {
        Gate::inspect('update', $business)->authorize();

        $provider = $business->paymentProviders()->where('payment_provider', $business->payment_provider)->first();

        if (!$provider) {
            $provider = PaymentProviderRepository::store($request, $business, $business->payment_provider);
            if($business->partner) {
                dispatch(new SetCustomPricingFromPartner($business->partner, $provider));
            }
        }

        if (
            $provider->payment_provider == PaymentProvider::STRIPE_SINGAPORE &&
            $provider->payment_provider_account_type == PaymentProviderAccountType::STRIPE_STANDARD_TYPE
        ) {
            // no need check verification?
        } else {
            if (!$business->verified_wit_my_info_sg) {
                return Response::redirectToRoute('dashboard.business.verification.home',[
                    $business->getKey(),
                ]);
            }
        }

        return Response::redirectToRoute('dashboard.business.payment-provider.stripe.home', [
            $business->getKey(),
        ]);
    }

    public function callback(Request $request)
    {
        $state = $request->get('state');
        $state = explode('|', $state);

        if (isset($state[1])) {
            return Response::redirectToRoute('dashboard.business.payment-provider.stripe.authorize', [
                    'business_id' => $state[1],
                ] + $request->all());
        }

        return Response::redirectToRoute('dashboard.home');
    }

    /**
     * Deauthorize Stripe account from our side.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function deauthorizeAccount(Request $request, Business $business)
    {
        Gate::inspect('update', $business)->authorize();

        $request->validate([
            'password' => [
                'required',
                'password',
            ],
        ]);

        BusinessRepository::removeStripeSingaporeAccount($business);

        return Response::redirectToRoute('dashboard.business.payment-provider.stripe.home', [
            'business_id' => $business->getKey(),
        ]);
    }
}
