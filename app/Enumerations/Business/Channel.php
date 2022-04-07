<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

class Channel extends Enumeration
{
    const LINK_SENT = 'link_sent'; // optional

    const DEFAULT = 'default'; // optional

    const PAYMENT_GATEWAY = 'payment_gateway'; // auto

    const POINT_OF_SALE = 'point_of_sale'; // auto

    const RECURRENT = 'recurrent'; // auto

    const STORE_CHECKOUT = 'store_checkout'; // auto
}
