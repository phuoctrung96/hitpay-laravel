<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

class ChargeStatus extends Enumeration
{
    const CANCELED = 'canceled';

    const FAILED = 'failed';

    const REFUNDED = 'refunded';

    const REQUIRES_CUSTOMER_ACTION = 'requires_customer_action';

    const REQUIRES_PAYMENT_METHOD = 'requires_payment_method';

    const SUCCEEDED = 'succeeded';

    const VOID = 'void';

    const PENDING = 'pending';

}
