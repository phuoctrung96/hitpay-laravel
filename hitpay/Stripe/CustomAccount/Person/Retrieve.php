<?php

namespace HitPay\Stripe\CustomAccount\Person;

class Retrieve extends Person
{
    /**
     * @param \App\Business\Person $businessPerson
     * @return \Stripe\Person
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function handle(\App\Business\Person $businessPerson) : \Stripe\Person
    {
        $this->getCustomAccount();

        return $this->getPerson($businessPerson->stripe_person_id);
    }
}
