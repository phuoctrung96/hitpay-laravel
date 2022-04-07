<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

class PaymentRequestStatus extends Enumeration
{
    const PENDING   = 'pending';

    const FAILED    = 'failed';

    const SENT      = 'sent';

    const COMPLETED = 'completed';

    const EXPIRED   = 'expired';

    const ALL   = 'all';
}
