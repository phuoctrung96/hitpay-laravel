<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'ca',
    'alpha_2' => 'ca',
    'alpha_3' => 'can',
    'name' => 'Canada',
    'currencies' => [
        CurrencyCode::CAD,
        CurrencyCode::USD
    ],
    'banks' => \HitPay\Data\Countries\CA::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\CA::paymentProviders()->toArray(),
];
