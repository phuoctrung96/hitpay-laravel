<?php

namespace App\Http\Controllers\Dashboard\Business\Settings\PaymentProviders\Stripe;

use App\Business;
use App\Http\Controllers\Controller;
use HitPay\Stripe\CustomAccount\AccountLink;
use HitPay\Stripe\CustomAccount\CustomAccount;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use HitPay\Stripe\CustomAccount\Sync;
use HitPay\Stripe\CustomAccount\Update;
use Illuminate\Http;
use Illuminate\Support\Facades;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CustomAccountController extends Controller
{
    /**
     * CustomAccountController Constructor
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the homepage of the "official" payment provider.
     *
     * @param  \App\Business  $business
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function showHomepage(Business $business) : Http\RedirectResponse
    {
        return Facades\Response::redirectToRoute('dashboard.business.payment-provider.home', [
            'business_id' => $business->getKey(),
        ]);

        // Facades\Gate::inspect('update', $business)->authorize();
        //
        // $updater = Update::new($business->payment_provider)->setBusiness($business);
        //
        // $this->preCheckPaymentProvider($updater);
        //
        // return Facades\Response::view('dashboard.business.settings.payment-providers.stripe.custom.home', [
        //     'business' => $business,
        //     'payment_provider' => $updater->getPaymentProvider(),
        //     'is_verified' => $updater->isCustomAccountVerified(false, false),
        // ]);
    }

    /**
     * Redirect user to Stripe account page.
     *
     * @param  \App\Business  $business
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function redirectToStripeAccountPage(Business $business) : Http\RedirectResponse
    {
        Facades\Gate::inspect('update', $business)->authorize();

        $accountLinkGenerator = AccountLink\Generate::new($business->payment_provider)->setBusiness($business);

        $this->preCheckPaymentProvider($accountLinkGenerator);

        $accountLink = $accountLinkGenerator->handle('account_update');

        return Facades\Response::redirectTo($accountLink);
    }

    /**
     * Handle the "return" from Stripe account page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Business  $business
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function callbackForStripeAccount(Http\Request $request, Business $business) : Http\RedirectResponse
    {
        Facades\Gate::inspect('update', $business)->authorize();

        $sync = Sync::new($business->payment_provider)->setBusiness($business);

        $this->preCheckPaymentProvider($sync);

        $sync->handle($request->input('state'));

        return Facades\Response::redirectToRoute('dashboard.business.settings.payment-providers.platform.homepage', [
            'business_id' => $business->getKey(),
        ]);
    }

    /**
     * Pre-check if the business has the Stripe custom account (payment provider) can be updated.
     *
     * @param  \HitPay\Stripe\CustomAccount\CustomAccount  $customAccount
     */
    private function preCheckPaymentProvider(CustomAccount $customAccount) : void
    {
        // This is only for business account with Stripe custom account, the try catch below is to check if the
        // business account has a valid Stripe custom connected account as payment provider.
        //
        try {
            $customAccount->getPaymentProvider();
        } catch (InvalidStateException $exception) {
            throw $this->featureUnavailableException();
        }
    }

    /**
     * Generate the feature unavailable exception.
     *
     * @return \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function featureUnavailableException() : HttpException
    {
        return new HttpException(400, 'This feature is unavailable for this business account.');
    }
}
