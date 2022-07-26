<?php

namespace App\Actions\Business\RecurringPlan;

use App\Business\PaymentProvider;

class CanCreateRecurringPlan extends Action
{
    /**
     * Check business can be create recurring plan
     *
     * @return boolean
     */
    public function process(): bool
    {
        $stripeAccount = $this->business->stripeAccount();

        if (!$stripeAccount instanceof PaymentProvider) {
            return false;
        }

        return true;
    }
}
