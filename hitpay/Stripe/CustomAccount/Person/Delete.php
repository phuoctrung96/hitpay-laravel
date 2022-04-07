<?php

namespace HitPay\Stripe\CustomAccount\Person;

use Illuminate\Support\Facades\Log;

class Delete extends Person
{
    /**
     * @param \App\Business\Person $businessPerson
     * @return void
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function handle(\App\Business\Person $businessPerson) : void
    {
        // Deletes an existing person’s relationship to the account’s legal entity.
        // Any person with a relationship for an account can be deleted through the API,
        // except if the person is the account_opener [representative].
        // If your integration is using the executive parameter,
        // you cannot delete the only verified executive on file.
        // more info:
        // https://stripe.com/docs/api/persons/delete

        $this->getCustomAccount();

        try {
            $this->getCustomAccount()->deletePerson(
                $this->stripeAccount->id,
                $businessPerson->stripe_person_id,
                [],
                ['stripe_version' => $this->stripeVersion]
            );
        } catch (\Exception $exception) {
            Log::info('cant delete person ' . $businessPerson->stripe_person_id .
                ' from business ' . $this->businessId . ' with error: ' .
                $exception->getMessage());
        }

        // even have error, we need to delete person because
        // it will create new again with new verification
        $businessPerson->delete();
    }
}
