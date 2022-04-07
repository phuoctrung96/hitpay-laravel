<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

class RecurringPlanStatus extends Enumeration
{
    const ACTIVE = 'active';

    const CANCELED = 'canceled';

    const COMPLETED = 'completed';

    const SCHEDULED = 'scheduled';
}
