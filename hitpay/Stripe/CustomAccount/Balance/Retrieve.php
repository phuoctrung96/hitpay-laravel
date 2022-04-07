<?php

namespace HitPay\Stripe\CustomAccount\Balance;

use HitPay\Stripe\Core;
use HitPay\Stripe\CustomAccount\Helper;
use Illuminate\Support\Collection;
use Stripe\Balance;

class Retrieve extends Core
{
    use Helper;

    /**
     * Get Stripe balance.
     *
     * @return \Illuminate\Support\Collection
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function handle()
    {
        $this->getCustomAccount();

        $balance = Balance::retrieve([
            'stripe_account' => $this->stripeAccount->id,
            'stripe_version' => $this->stripeVersion,
        ]);

        $balanceGroup = Collection::make();

        foreach ([
            'available',
            'instant_available',
            'connect_reserved', // This is for our platform balance, not applicable here, actually.
            'pending',
            'issuing', // We are not going to use this.
        ] as $type) {
            if (!is_array($balance->{$type})) {
                continue;
            }

            foreach ($balance->{$type} as ${$type}) {
                $balanceGroup->push([
                    'type' => $type,
                    'currency' => ${$type}->currency,
                    'amount' => ${$type}->amount,
                ]);
            }
        }

        return $balanceGroup->groupBy('currency');
    }
}
