<?php

namespace App\Actions\Business\Stripe;

use App\Actions\Business\Action as BaseAction;
use App\Actions\Exceptions\BadRequest;
use App\Business;
use App\Providers\AppServiceProvider;
use Exception;
use HitPay\Data\Countries\Objects\PaymentProvider;
use Illuminate\Support\Facades;
use Stripe\Stripe;

abstract class Action extends BaseAction
{
    protected ?Business\PaymentProvider $businessPaymentProvider;

    protected ?string $businessPaymentProviderCode;

    protected bool $businessPaymentProviderLoaded = false;

    /**
     * Get the related payment provider of the business.
     *
     * @return \App\Business\PaymentProvider
     * @throws \Exception
     */
    protected function getBusinessPaymentProvider() : Business\PaymentProvider
    {
        if ($this->businessPaymentProviderLoaded) {
            return $this->businessPaymentProvider;
        }

        $this->businessPaymentProviderLoaded = true;

        $eligibleStripePaymentProviders = $this->country->paymentProviders()->where('official_code', 'stripe');

        // (1) We retrieve all Stripe related payment providers of the business.
        //
        $businessPaymentProviders = $this->business->paymentProviders()
            ->whereIn('payment_provider', $eligibleStripePaymentProviders->pluck('code'))
            ->get();

        // (2) If the business has more than 1 Stripe related payment providers, we reject it.
        //
        if ($businessPaymentProviders->count() > 1) {
            Facades\Log::critical("The business (ID : {$this->business->getKey()}) is weird, it has more than 1 Stripe related payment provider. Please check.");
        }

        $this->businessPaymentProvider = $businessPaymentProviders->first();

        // (3) If the business has no Stripe related payment providers, we reject it too, but this is a bad request.
        //
        if (!( $this->businessPaymentProvider instanceof Business\PaymentProvider )) {
            throw new BadRequest("The business (ID : {$this->business->getKey()}) has no Stripe related payment provider.");
        }

        $this->businessPaymentProviderCode = $this->businessPaymentProvider->payment_provider;

        /**
         * @var \HitPay\Data\Countries\Objects\PaymentProvider $eligibleStripePaymentProvider
         */
        $eligibleStripePaymentProvider = $eligibleStripePaymentProviders
            ->where('code', $this->businessPaymentProvider->payment_provider)
            ->first();

        $stripeSecret = Facades\Config::get("services.stripe.{$eligibleStripePaymentProvider->getCountry()}.secret");

        // (4) We check if the Stripe configuration exist for the selected Stripe payment provider.
        //
        if (is_null($stripeSecret)) {
            throw new Exception("The configuration for payment provider '{$this->businessPaymentProvider->payment_provider}' isn't available. PLEASE CHECK IMMEDIATELY.");
        }

        Stripe::setApiKey($stripeSecret);
        Stripe::setApiVersion(AppServiceProvider::STRIPE_VERSION);

        return $this->businessPaymentProvider;
    }

    protected function getStripeConfigurations() : ?PaymentProvider
    {
        return $this->country ? $this->country->paymentProviders()->where('official_code', 'stripe')->first() : null;
    }
}
