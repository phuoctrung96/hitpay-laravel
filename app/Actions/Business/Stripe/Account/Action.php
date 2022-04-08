<?php

namespace App\Actions\Business\Stripe\Account;

use App\Actions\Business\Action as BaseAction;
use App\Business\PaymentProvider;
use Exception;

abstract class Action extends BaseAction
{
    protected $stripeAccountId = null;

    protected $stripeAccount = null;

    protected $paymentProvider = null;

    /***
     * @param $event
     * @return $this
     */
    public function setBusinessFromEvent($event)
    {
        if (isset($event->account)) {
            // handle for api version 2017-04-06
            $this->stripeAccountId = $event->account;
        } else {
            // handle for api latest api version
            $this->stripeAccount = $event->data->object;
            $this->stripeAccountId = $this->stripeAccount->id;
        }

        $paymentProvider = PaymentProvider::query()
            ->where('payment_provider', $this->data['payment_provider'])
            ->where('payment_provider_account_id', $this->stripeAccountId)
            ->first();

        if ($paymentProvider === null) {
            throw new Exception("The stripe account (ID : {$this->stripeAccountId}) has no right to the provider");
        }

        $this->paymentProvider = $paymentProvider;

        $business = $paymentProvider->business;

        if ($business === null) {
            throw new Exception("The stripe account (ID : {$this->stripeAccountId}) has no right to the business");
        }

        parent::business($business);

        return $this;
    }

    /***
     * @param $event
     * @return $this
     */
    public function withStripeEvent($event) : self
    {
        return $this->setBusinessFromEvent($event);
    }
}
