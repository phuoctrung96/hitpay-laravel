<?php

namespace App\Actions\Business\Charges\Stripe;

use App\Actions\Business\Charges\Action as BaseAction;
use Exception;
use Illuminate\Support\Facades;
use Stripe\Stripe;

abstract class Action extends BaseAction
{
    /**
     * @inheritdoc
     */
    protected function paymentProviderOfficialCode() : string
    {
        return 'stripe';
    }

    /**
     * @inheritdoc
     */
    protected function postGetBusinessPaymentProvider() : void
    {
        $country = $this->paymentProviderConfiguration->getCountry();

        $stripeSecret = Facades\Config::get("services.stripe.{$country}.secret");

        // (4) We check if the Stripe configuration exist for the selected Stripe payment provider.
        //
        if (is_null($stripeSecret)) {
            throw new Exception("The configuration for payment provider '{$this->businessPaymentProvider->payment_provider}' isn't available. PLEASE CHECK IMMEDIATELY.");
        }

        Stripe::setApiKey($stripeSecret);
    }
}
