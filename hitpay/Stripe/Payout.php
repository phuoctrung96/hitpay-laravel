<?php

namespace HitPay\Stripe;

use Stripe\Payout as StripePayout;

class Payout extends Core
{
    /**
     * Create the payouts list for a Stripe account.
     *
     * @param int $limit
     *
     * @return \Stripe\Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function index(int $limit = 50)
    {
        return StripePayout::all([
            'limit' => $limit,
        ]);
    }
}
