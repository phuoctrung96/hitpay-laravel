<?php

namespace App\Actions\Business\Settings\Verification;

use HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException;
use HitPay\Stripe\CustomAccount\Exceptions\GeneralException;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use HitPay\Stripe\CustomAccount\Person;
use Illuminate\Support\Facades;

class Destroy extends Action
{
    /**
     * @return void
     * @throws AccountNotFoundException
     * @throws GeneralException
     * @throws InvalidStateException
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function process() : void
    {
        $verification = $this->business->verifications()->latest()->first();

        $businessPersons = $verification->persons()->get();

        foreach ($businessPersons as $businessPerson) {
            $relationships = $businessPerson->relationship;

            if (
                is_array($relationships) &&
                array_key_exists('representative', $relationships) &&
                $relationships['representative'] == true) {
                // skip adding user representative to delete
                continue;
            } else {
                $this->deletePerson($businessPerson);
                $verification->persons()->detach($businessPerson->getKey());
            }
        }

        // remove all verification
        $this->business->verifications()->delete();

        $this->business->update([
            'verified_wit_my_info_sg' => false,
        ]);
    }

    /**
     * @param \App\Business\Person $businessPerson
     * @return void
     * @throws AccountNotFoundException
     * @throws GeneralException
     * @throws InvalidStateException
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function deletePerson(\App\Business\Person $businessPerson) : void
    {
        $handler = Person\Delete::new($this->business->payment_provider)
            ->setBusiness($this->business);

        try {
            $handler->getCustomAccount();
        } catch (GeneralException $exception) {
            if ($exception instanceof InvalidStateException) {
                Facades\Log::critical("Trying but unable to create a person for the business (ID : {$this->businessId}) which is not using Stripe custom account.");
            } elseif ($exception instanceof AccountNotFoundException) {
                Facades\Log::critical("Trying but unable to create a person for a non-Stripe custom connected business account (ID : {$this->businessId}).");
            }

            throw $exception;
        }

        $handler->handle($businessPerson);
    }
}
