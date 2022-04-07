<?php

namespace App\Actions\Business\Stripe\Charge;

use App\Actions\Business\Stripe\Action as BaseAction;
use App\Business;
use Exception;

abstract class Action extends BaseAction
{
    protected ?Business\Charge $businessCharge = null;

    protected ?string $businessChargeId = null;

    protected ?Business\PaymentIntent $businessPaymentIntent = null;

    protected ?string $businessPaymentIntentId = null;

    const PAYMENT_INTENT = 'payment_intent';

    const SOURCE = 'source';

    /**
     * Set the charge.
     *
     * @param  \App\Business\Charge  $businessCharge
     *
     * @return \App\Actions\Business\Stripe\Charge\Action
     * @throws \Exception
     */
    public function businessCharge(Business\Charge $businessCharge) : self
    {
        if ($this->businessId && $this->businessId !== $businessCharge->business_id) {
            throw new Exception("The charge (ID : {$businessCharge->getKey()}) doesn't belonged to the business (ID : {$this->businessId})");
        }

        if ($this->businessPaymentIntent && $this->businessPaymentIntent->business_charge_id !== $businessCharge->getKey()) {
            throw new Exception("The charge (ID : {$businessCharge->getKey()}) has no right to the payment intent (ID : {$this->businessPaymentIntentId})");
        }

        $this->businessCharge = $businessCharge;
        $this->businessChargeId = $businessCharge->getKey();

        return $this;
    }

    /**
     * Initiate a new instance starts with setting the charge.
     *
     * @param  \App\Business\Charge  $businessCharge
     *
     * @return static
     * @throws \Exception
     */
    public static function withBusinessCharge(Business\Charge $businessCharge) : self
    {
        return ( new static )->businessCharge($businessCharge);
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function business(Business $business) : self
    {
        if ($this->businessCharge && $this->businessCharge->business_id !== $business->getKey()) {
            throw new Exception("The business (ID : {$business->getKey()}) has no right to the charge (ID : {$this->businessChargeId})");
        }

        if ($this->businessPaymentIntent && $this->businessPaymentIntent->business_id !== $business->getKey()) {
            throw new Exception("The business (ID : {$business->getKey()}) has no right to the payment intent (ID : {$this->businessPaymentIntentId})");
        }

        return parent::business($business);
    }

    /**
     * Set the payment intent.
     *
     * @param  \App\Business\PaymentIntent  $businessPaymentIntent
     *
     * @return $this
     * @throws \Exception
     */
    public function businessPaymentIntent(Business\PaymentIntent $businessPaymentIntent) : self
    {
        if ($this->businessId && $this->businessId !== $businessPaymentIntent->business_id) {
            throw new Exception("The payment intent (ID : {$businessPaymentIntent->getKey()}) doesn't belonged to the business (ID : {$this->businessId})");
        }

        if ($this->businessChargeId && $this->businessChargeId !== $businessPaymentIntent->business_charge_id) {
            throw new Exception("The payment intent (ID : {$businessPaymentIntent->getKey()}) doesn't belonged to the charge (ID : {$this->businessChargeId})");
        }

        $this->businessPaymentIntent = $businessPaymentIntent;
        $this->businessPaymentIntentId = $businessPaymentIntent->getKey();

        return $this;
    }

    /**
     * Initiate a new instance starts with setting the payment intent.
     *
     * @param  \App\Business\PaymentIntent  $businessPaymentIntent
     *
     * @return static
     * @throws \Exception
     */
    public static function withBusinessPaymentIntent(Business\PaymentIntent $businessPaymentIntent) : self
    {
        return ( new static )->businessPaymentIntent($businessPaymentIntent);
    }

    protected function getExchangeRate(string $from, string $to)
    {
        if ($from === $to) {
            return 1;
        }

        // todo
        return 0.20735;
    }
}
