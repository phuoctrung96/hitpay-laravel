<?php

namespace HitPay\Stripe\CustomAccount\Person;

use HitPay\Stripe\Core;
use HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException;
use HitPay\Stripe\CustomAccount\Helper;
use Stripe;

abstract class Person extends Core
{
    use Helper;

    /**
     * Get the person of a custom account from Stripe.
     *
     * @param  string  $id
     * @param  bool  $strict
     *
     * @return \Stripe\Person|null
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getPerson(string $id, bool $strict = true) : ?Stripe\Person
    {
        $person = $this->getPersons()->retrieve($id);

        if ($strict && !( $person instanceof Stripe\Person )) {
            throw new AccountNotFoundException("The person (Stripe ID : {$id}) for this custom account could not be found.");
        }

        return $person;
    }

    /**
     * Get the collection of persons of a custom account from Stripe.
     *
     * @param  bool  $strict
     *
     * @return \Stripe\Collection
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getPersons(bool $strict = false) : Stripe\Collection
    {
        $persons = $this->getCustomAccount()->persons()->all();

        if ($strict && $persons->count() === 0) {
            throw new AccountNotFoundException('There are no persons in this custom account.');
        }

        return $persons;
    }
}
