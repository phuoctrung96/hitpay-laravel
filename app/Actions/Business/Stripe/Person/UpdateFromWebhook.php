<?php

namespace App\Actions\Business\Stripe\Person;

use Illuminate\Support\Facades\Log;

class UpdateFromWebhook extends Action
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

        if ($businessPaymentProvider == null) {
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
            // sometime user delete verification, mean will delete businessPerson too
            // so keep it throw exception, already adding try catch from webhook so only warning log
            Log::critical("The stripe account (ID : {$this->stripeAccountId}) has no
                active person '{$this->stripePersonId}' to the business {$this->businessId}");

            return false;
        }

        if (isset($this->stripePerson->full_name_aliases)) {
            if (isset($this->stripePerson->full_name_aliases[0])) {
                $activeBusinessPerson->alias_name = $this->stripePerson->full_name_aliases[0];
            }
        } else {
            $activeBusinessPerson->alias_name = null;
        }

        $activeBusinessPerson->first_name = $this->stripePerson->first_name ?? null;
        $activeBusinessPerson->last_name = $this->stripePerson->last_name ?? null;
        $activeBusinessPerson->email = $this->stripePerson->email ?? null;

        $dob = $this->stripePerson->dob->year . '-' . $this->stripePerson->dob->month . '-' . $this->stripePerson->dob->day;
        $activeBusinessPerson->dob = $dob;

        $activeBusinessPerson->country = $this->stripePerson->address->country ?? null;
        $activeBusinessPerson->state = $this->stripePerson->address->state ?? null;
        $activeBusinessPerson->city = $this->stripePerson->address->state ?? null;
        $activeBusinessPerson->address = $this->stripePerson->address->line1 ?? null;
        $activeBusinessPerson->address2 = $this->stripePerson->address->line2 ?? null;
        $activeBusinessPerson->postal_code = $this->stripePerson->address->postal_code ?? null;
        $activeBusinessPerson->phone = $this->stripePerson->phone ?? null;

        $stripeRelationship = $this->stripePerson->relationship;
        $activeBusinessPerson->title = $stripeRelationship->title ?? null;

        $hitPayRelationship['owner'] = $stripeRelationship->owner ?? false;
        $hitPayRelationship['director'] = $stripeRelationship->director ?? false;
        $hitPayRelationship['executive'] = $stripeRelationship->executive ?? false;
        $hitPayRelationship['representative'] = $stripeRelationship->representative ?? false;

        $activeBusinessPerson->relationship = $hitPayRelationship;
        $activeBusinessPerson->percent_ownership = $stripeRelationship->percent_ownership ?? null;

        $activeBusinessPerson->save();

        return true;
    }
}
