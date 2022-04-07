<?php

namespace HitPay\Stripe\CustomAccount\Balance\Transaction;

use HitPay\Stripe\Core;
use HitPay\Stripe\CustomAccount\Helper;
use Illuminate\Support\Collection;
use Stripe\BalanceTransaction;

class IndexByPayout extends Core
{
    use Helper;

    protected string $stripePayoutId;

    public function handle(string $stripePayoutId)
    {
        $this->getCustomAccount();

        $this->stripePayoutId = $stripePayoutId;

        $data = [];

        $lastId = null;

        do {
            $balanceTransactions = $this->retrieve($lastId);

            $data = array_merge($data, $balanceTransactions->data);

            if ($balanceTransactions->has_more) {
                $lastId = $balanceTransactions->last()->id;
            }
        } while ($balanceTransactions->has_more);

        return Collection::make($data)->unique('id');
    }

    protected function retrieve(string $last = null)
    {
        $parameters = [
            'payout' => $this->stripePayoutId,
            'limit' => 100,
            'expand' => [
                'data.source.charge.source_transfer',
                'data.source.destination',
                'data.source.source_transfer',
            ],
        ];

        if (is_string($last)) {
            $parameters['starting_after'] = $last;
        }

        return BalanceTransaction::all($parameters, [
            'stripe_account' => $this->stripeAccount->id,
            'stripe_version' => $this->stripeVersion,
        ]);
    }
}
