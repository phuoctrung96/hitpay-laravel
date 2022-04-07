<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

class Event extends Enumeration
{
    const DAILY_COLLECTION = 'daily_collection';

    const DAILY_PAYOUT = 'daily_payout';

    const NEW_ORDER = 'new_order';

    const PENDING_ORDER = 'pending_order';

    const INCOMING_PAYMENT = 'incoming_payment';

    const CUSTOMER_RECEIPT = 'customer_receipt';
}
