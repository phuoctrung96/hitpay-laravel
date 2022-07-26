<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

class RecurringBillingEvent extends Enumeration
{
    const CHARGE_SUCCESS = 'charge';

    const RECURRENT_BILLING_STATUS = 'billing_status';
}
