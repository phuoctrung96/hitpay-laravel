<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

class RecurringCycle extends Enumeration
{
    const WEEKLY = 'weekly';

    const BIWEEKLY = 'biweekly';

    const MONTHLY = 'monthly';

    const QUARTERLY = 'quarterly';

    const YEARLY = 'yearly';

    const SAVE_CARD = 'save_card';
}
