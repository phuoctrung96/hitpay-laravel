<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

class PaymentProviderStatus extends Enumeration
{
    const ENABLED = 'enabled';

    const REQUIRES_INFORMATION = 'requires_information';

    const PENDING_APPROVAL = 'pending_approval';

}
