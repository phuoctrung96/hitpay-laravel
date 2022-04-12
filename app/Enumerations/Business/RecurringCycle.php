<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

class RecurringCycle extends Enumeration
{
    const WEEKLY = 'weekly';

    const MONTHLY = 'monthly';

    const YEARLY = 'yearly';

    const SAVE_CARD = 'save_card';
}
