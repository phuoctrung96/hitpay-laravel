<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'au',
    'alpha_2' => 'au',
    'alpha_3' => 'aus',
    'name' => 'Australia',
    'currencies' => [
        CurrencyCode::AUD,
        CurrencyCode::USD,
        CurrencyCode::NZD
    ],
    'banks' => \HitPay\Data\Countries\AU::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\AU::paymentProviders()->toArray(),
];
