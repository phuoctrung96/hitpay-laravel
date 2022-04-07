<?php

namespace HitPay\Stripe\CustomAccount\Person;

use HitPay\Stripe\CustomAccount\CustomAccount;

class Push extends CustomAccount
{
    public function handle(array $person) : void
    {
        $this->getCustomAccount()->createPerson($this->stripeAccount->id, $person, [
            'stripe_version' => $this->stripeVersion,
        ]);
    }
}
