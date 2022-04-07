<?php

namespace App\Observers;

use App\Business\BusinessReferral;

class BusinessReferralObserver
{
    /**
     * Handle the business referral "created" event.
     *
     * @param  \App\Business\BusinessReferral  $businessReferral
     * @return void
     */
    public function creating(BusinessReferral $businessReferral)
    {
        $businessReferral->ends_at = clone($businessReferral->starts_at)->addYear();
    }
}
