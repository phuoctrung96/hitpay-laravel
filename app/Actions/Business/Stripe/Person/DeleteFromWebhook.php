<?php

namespace App\Actions\Business\Stripe\Person;

use Illuminate\Support\Facades\Log;

class DeleteFromWebhook extends Action
{
    /**
     * @return bool
     */
    public function process() : bool
    {
        if (!$this->validateProcess()) {
            return false;
        }

        $businessPaymentProvider = $this->business->paymentProviders()
            ->where('payment_provider', $this->paymentProviderCode)
            ->first();

        if ($businessPaymentProvider === null) {
            Log::critical("Warning: Business payment provider of '{$this->paymentProviderCode}' is not
                found for business (ID : {$this->business->getKey()})");

            return false;
        }

        $businessPersons = $businessPaymentProvider->persons()->get();

        $foundPerson = false;

        $activeBusinessPerson = null;

        foreach ($businessPersons as $businessPerson) {
            if (
                $businessPerson->id == $this->hitpayPersonId &&
                $businessPerson->stripe_person_id == $this->stripePersonId
            ) {
                $foundPerson = true;
                $activeBusinessPerson = $businessPerson;
                break;
            }
        }

        if (!$foundPerson) {
            Log::critical("No person found in our database from business (ID : $this->business->getKey())");

            return false;
        }

        $businessPaymentProvider->persons()->detach($activeBusinessPerson->getKey());

        $activeBusinessPerson->delete();

        return true;
    }
}
