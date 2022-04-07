<?php

namespace HitPay\Stripe;

use Stripe\Token as StripeToken;

class Token extends Core
{
    /**
     * @param string $token
     *
     * @return \Stripe\Token
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function retrieve(string $token)
    {
        return StripeToken::retrieve($token);
    }
}
