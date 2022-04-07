<?php

namespace HitPay\Stripe;

use Stripe\Customer as StripeCustomer;

class PaymentCard extends Core
{
    /**
     * Create a card for a Stripe customer.
     *
     * @param string $customerId
     * @param string $source
     *
     * @return \Stripe\ApiResource
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function create(string $customerId, string $source)
    {
        return StripeCustomer::createSource($customerId, [
            'source' => $source,
        ]);
    }

    /**
     * Delete a card from a Stripe customer.
     *
     * @param string $customerId
     * @param string $stripeCardId
     *
     * @return \Stripe\ApiResource
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function delete(string $customerId, string $stripeCardId)
    {
        return StripeCustomer::deleteSource($customerId, $stripeCardId);
    }
}
