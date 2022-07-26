<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

class OnboardingSteps extends Enumeration
{
    const HIDE = 'hide_get_started';

    const STORE_URL = 'store_url';

    const THEME = 'theme';

    const PRODUCTS = 'products';

    const SHIPPING_PICKUP = 'shipping_and_pickup';
}
