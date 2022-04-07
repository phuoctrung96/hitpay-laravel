<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

class ShippingCalculation extends Enumeration
{
    const FLAT = 'flat';

    const FEE_PER_UNIT = 'fee_per_unit';
}
