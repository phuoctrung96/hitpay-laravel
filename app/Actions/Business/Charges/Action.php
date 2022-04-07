<?php

namespace App\Actions\Business\Charges;

use App\Actions\Business\Action as BaseAction;
use App\Actions\Exceptions\BadRequest;
use App\Business;
use Exception;
use HitPay\Data\Countries\Objects\PaymentProvider;
use Illuminate\Support\Facades;

abstract class Action extends BaseAction
{
    protected Business\PaymentProvider $businessPaymentProvider;

    protected string $businessPaymentProviderCode;

    protected bool $businessPaymentProviderLoaded = false;

    protected PaymentProvider $paymentProviderConfiguration;

    /**
     * Get the related payment provider of the business.
     *
     * @return \App\Business\PaymentProvider
     * @throws \App\Actions\Exceptions\BadRequest
     */
    protected function getBusinessPaymentProvider() : Business\PaymentProvider
    {
        if ($this->businessPaymentProviderLoaded) {
            return $this->businessPaymentProvider;
        }

        $this->businessPaymentProviderLoaded = true;

        if (!( $this->business instanceof Business )) {
            throw new Exception('The business must be set before calling this function.');
        }

        $paymentProviderOfficialCode = $this->paymentProviderOfficialCode();

        if (blank($paymentProviderOfficialCode)) {
            throw new Exception('The business payment provider official code is not defined in the child class.');
        }

        // We will get all the available and related payment providers of the selected country, usually each country
        // will have only one. E.g. HitPay uses two different Stripe accounts from the same country.
        //
        // Although the above scenario might happen, if it happens, we should not  allow them to have multiple payment
        // providers with same official code too.
        //
        $availablePaymentProviders = $this->country->paymentProviders()
            ->where('official_code', $paymentProviderOfficialCode);

        if ($availablePaymentProviders->count() === 0) {
            throw new Exception(
                "The business (ID : {$this->business->getKey()}) has enabled the '{$paymentProviderOfficialCode}'-related payment provider which is currently unavailable, or which the configuration is missing. PLEASE CHECK!"
            );
        }

        $businessPaymentProviders = $this->business->paymentProviders()
            ->whereIn('payment_provider', $availablePaymentProviders->pluck('code'))
            ->get();

        $businessPaymentProviderCount = $businessPaymentProviders->count();

        if ($businessPaymentProviderCount === 0) {
            throw new BadRequest(
                "A '{$paymentProviderOfficialCode}'-related payment provider must be enabled to proceed."
            );
        }

        if ($businessPaymentProviderCount > 1) {
            Facades\Log::critical(
                "The business (ID : {$this->business->getKey()}) has more than 1 '{$paymentProviderOfficialCode}'-related payment provider. Please check."
            );
        }

        // If the business has more than one related payment providers, we will choose the one added lastly.
        //
        $this->businessPaymentProvider = $businessPaymentProviders->sortByDesc('created_at')->first();
        $this->businessPaymentProviderCode = $this->businessPaymentProvider->payment_provider;
        $this->paymentProviderConfiguration = $availablePaymentProviders
            ->where('code', $this->businessPaymentProvider->payment_provider)
            ->first();

        $this->postGetBusinessPaymentProvider();

        return $this->businessPaymentProvider;
    }

    /**
     * Get the official code of the payment provider.
     *
     * @return string|null
     */
    protected function paymentProviderOfficialCode() : ?string
    {
        return null;
    }

    /**
     * Register post activities after the business payment provider is retrieved.
     *
     * @return void
     */
    protected function postGetBusinessPaymentProvider() : void
    {
        //
    }
}
