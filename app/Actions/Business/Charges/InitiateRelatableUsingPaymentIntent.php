<?php

namespace App\Actions\Business\Charges;

use App\Actions\Business\Stripe\Charge\Action;
use App\Actions\Exceptions;
use App\Business;
use App\Enumerations\Business\ChargeStatus;
use Exception;
use HitPay\Data\Countries;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades;

trait InitiateRelatableUsingPaymentIntent
{
    protected string $paymentProviderName;

    protected Carbon $now;

    protected string $expectedBusinessChargeId;

    /**
     * Get the business relating to the Stripe source.
     *
     * @return void
     * @throws \Exception
     */
    protected function initiateBusiness() : void
    {
        $this->business = $this->businessPaymentIntent->business;

        if (!( $this->business instanceof Business )) {
            throw new Exception("The business doesn't exist or invalid.");
        }

        $this->businessId = $this->business->getKey();

        $this->country = Countries::get($this->business->country);
    }

    /**
     * Get the business charge relating to the Stripe source.
     *
     * @return void
     * @throws \App\Actions\Exceptions\BadRequest
     * @throws \App\Actions\Exceptions\UnexpectedError
     */
    protected function initiateBusinessCharge() : void
    {
        $this->businessCharge = $this->businessPaymentIntent->charge;

        if (!( $this->businessCharge instanceof Business\Charge )) {
            throw new Exceptions\BadRequest("The charge can't be found for this source.");
        }

        if ($this->businessCharge->status !== ChargeStatus::REQUIRES_PAYMENT_METHOD) {
            throw new Exceptions\BadRequest("The status of the charge doesn't allow to accept payment now, status '{$this->businessCharge->status}' received.");
        }

        $this->businessChargeId = $this->businessCharge->getKey();

        if ($this->businessPaymentIntent->business_id !== $this->businessCharge->business_id
            || $this->businessPaymentIntent->currency !== $this->businessCharge->currency
            || $this->businessPaymentIntent->amount !== $this->businessCharge->amount) {
            $this->log(<<<_MESSAGE
=====================================
= WARNING  :  Charge Data Unmatched =
=====================================

The data of the payment intent isn't match with the data of the charge.

    ======================
        Payment Intent
    ======================
    ID           :  {$this->businessPaymentIntentId}
    Business ID  :  {$this->businessPaymentIntent->business_id}
    Currency     :  {$this->businessPaymentIntent->currency}
    Amount       :  {$this->businessPaymentIntent->amount}

    ======================
            Charge
    ======================
    ID           :  {$this->businessChargeId}
    Business ID  :  {$this->businessCharge->business_id}
    Currency     :  {$this->businessCharge->currency}
    Amount       :  {$this->businessCharge->amount}
_MESSAGE
            );

            throw new Exceptions\UnexpectedError("The data of the payment intent (ID : {$this->businessPaymentIntentId}) isn't match with the data of the charge (ID : $this->businessChargeId), check `{$this->logFilename}` for details.");
        }

        if ($this->expectedBusinessChargeId !== $this->businessChargeId) {
            Facades\Log::critical("The business charge (ID : {$this->businessChargeId}) is not as expected, the business charged ID in the Stripe source (ID : {$this->stripeSource->id}) is {$this->expectedBusinessChargeId}. This payment is still allowed, just check what's happening.");
        }
    }

    // = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = //
    //                                                                             //
    //          THE BELOW METHODS WERE OVERRIDDEN TO PREVENT THE RELATED           //
    //                          VALUES TO BE SET MANUALLY                          //
    //                                                                             //
    // = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = //

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function business(Business $business) : Action
    {
        throw new Exception('Setting business is prohibited in this class.');
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function businessCharge(Business\Charge $businessCharge) : Action
    {
        throw new Exception('Setting business charge is prohibited in this class.');
    }
}
