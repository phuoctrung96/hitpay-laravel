<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

class CouponType extends Enumeration
{
    const ALL_PRODUCT = 1;

    const SPECIFIC_CATEGORIES = 2;

    const SPECIFIC_PRODUCTS = 3;
}
