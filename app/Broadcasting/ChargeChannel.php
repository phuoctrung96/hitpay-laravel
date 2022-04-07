<?php

namespace App\Broadcasting;

use App\Business\Charge;
use App\Enumerations\Business\ChargeStatus;

class ChargeChannel
{
    public function join(Charge $charge = null)
    {
        return $charge && $charge->status === ChargeStatus::REQUIRES_PAYMENT_METHOD;
    }
}
