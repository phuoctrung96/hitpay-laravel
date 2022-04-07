<?php

namespace HitPay\Stripe\CustomAccount\Person;

use Illuminate\Support\Facades;

class Update extends Person
{
    /**
     * @param \App\Business\Person $businessPerson
     * @param array $params
     * @return \App\Business\Person
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function handle(\App\Business\Person $businessPerson, array $params) : \App\Business\Person
    {
        $this->getCustomAccount();

        if (!Facades\App::environment('production')) {
            Facades\Log::info(json_encode($params));
        }

        $stripePerson = $this->getCustomAccount()->updatePerson(
            $this->stripeAccount->id,
            $businessPerson->stripe_person_id,
            $params,
            ['stripe_version' => $this->stripeVersion]
        );

        $businessPerson->data = $stripePerson;
        $businessPerson->save();

        return $businessPerson;
    }
}
