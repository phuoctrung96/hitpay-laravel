<?php

namespace HitPay\Stripe\CustomAccount\Balance\Transaction;

use HitPay\Stripe\Collection;
use HitPay\Stripe\Core;
use HitPay\Stripe\CustomAccount\Helper;
use Stripe\BalanceTransaction;

class Index extends Core
{
    use Helper;

    public function handle(string $last = null, string $first = null, int $limit = 25)
    {
        $this->getCustomAccount();

        $parameters = [
            'limit' => $limit,
            'expand' => [
                'data.source.source_transfer',
                'data.source.charge.source_transfer',
                'data.source.destination',
            ],
        ];

        if (is_string($first)) {
            $parameters['ending_before'] = $first;
        } elseif (is_string($last)) {
            $parameters['starting_after'] = $last;
        }

        $balanceTransaction = BalanceTransaction::all($parameters, [
            'stripe_account' => $this->stripeAccount->id,
            'stripe_version' => $this->stripeVersion,
        ]);

        return new Collection($balanceTransaction, $limit);
    }
}
