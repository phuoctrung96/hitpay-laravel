<?php

namespace HitPay\Stripe;

use Stripe\Customer as StripeCustomer;

class Customer extends Core
{
    /**
     * Create Stripe customer.
     *
     * @param string $description
     *
     * @return \Stripe\Customer
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function create(string $description) : StripeCustomer
    {
        return StripeCustomer::create(compact('description'));
    }
}
