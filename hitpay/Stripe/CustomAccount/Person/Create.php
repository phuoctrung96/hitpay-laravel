<?php

namespace HitPay\Stripe\CustomAccount\Person;

use Illuminate\Support\Facades;

class Create extends Person
{
    protected array $currentStripePersons = [];

    protected bool $isRepresentative = false;

    /**
     * @param bool $isRepresentative
     * @return $this
     */
    public function isRepresentative(bool $isRepresentative) : self
    {
        $this->isRepresentative = $isRepresentative;

        return $this;
    }

    /**
     * @param \App\Business\Person $businessPerson
     * @param array $params
     * @return \App\Business\Person
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Exception
     */
    public function handle(\App\Business\Person $businessPerson, array $params) : \App\Business\Person
    {
        $this->getCustomAccount();

        $representativePerson = null;

        if ($this->isRepresentative) {
            sleep(5);
            // the person is the account_opener [representative].
            // more info:
            // https://stripe.com/docs/api/persons/delete
            $representativePerson = $this->getRepresentativePerson();
        }

        if (!Facades\App::environment('production')) {
            Facades\Log::info(json_encode($params));
        }

        $stripePerson = null;

        if ($this->isRepresentative && count($representativePerson) == 0) {
            // sometime got rate limited issue like this message:
            // This object cannot be accessed right now because
            // another API request or Stripe process is currently accessing it.
            // If you see this error intermittently, retry the request.
            // If you see this error frequently and are making multiple concurrent
            // requests to a single object, make your requests serially or at a lower rate
            sleep(5);

            $stripePerson = $this->getCustomAccount()->createPerson(
                $this->stripeAccount->id,
                $params,
                ['stripe_version' => $this->stripeVersion]
            );
        }

        if ($this->isRepresentative && $representativePerson) {
            // no need update, we just retrieve representative person
            // because representative person is from owner of business.
            $stripePerson = $this->getPerson($representativePerson['id']);
        }

        if (!$this->isRepresentative) {
            $samePerson = $this->checkSimilarPerson($params);

            if (!isset($samePerson['id'])) {
                sleep(5);

                $stripePerson = $this->getCustomAccount()->createPerson(
                    $this->stripeAccount->id,
                    $params,
                    ['stripe_version' => $this->stripeVersion]
                );
            } else {
                $stripePerson = $this->getPerson($samePerson['id']);
            }
        }

        if ($stripePerson === null) {
            throw new \Exception('Stripe person empty response for business ' . $this->business->getKey() . ' when creating person ' . $businessPerson->getKey());
        }

        $businessPerson->stripe_person_id = $stripePerson->id;
        $businessPerson->data = $stripePerson;
        $businessPerson->save();

        return $businessPerson;
    }

    /**
     * @return array
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function getCurrentPersons() : array
    {
        if (count($this->currentStripePersons) == 0) {
            $this->currentStripePersons = $this->getPersons()->toArray();
        }

        return $this->currentStripePersons;
    }

    /**
     * @param array $params
     * @return array
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function checkSimilarPerson(array $params) : array
    {
        $this->currentStripePersons = $this->getCurrentPersons();

        if (count($this->currentStripePersons) == 0) {
            return [];
        }

        $samePerson = [];

        foreach ($this->currentStripePersons['data'] as $person) {
            if (
                $person['first_name'] == $params['first_name'] &&
                $person['last_name'] == $params['last_name'] &&
                $person['dob']['year'] == $params['dob']['year'] &&
                $person['dob']['month'] == $params['dob']['month'] &&
                $person['dob']['day'] == $params['dob']['day']
            ) {
                $samePerson = $person;
                break;
            }
        }

        return $samePerson;
    }

    /**
     * @return array
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function getRepresentativePerson() : array
    {
        $this->currentStripePersons = $this->getCurrentPersons();

        if (count($this->currentStripePersons) == 0) {
            return [];
        }

        $representativePerson = [];

        foreach ($this->currentStripePersons['data'] as $person) {
            $relationship = $person['relationship'];

            if ($relationship['representative'] == 'true') {
                $representativePerson = $person;
                break;
            }
        }

        return $representativePerson;
    }
}
