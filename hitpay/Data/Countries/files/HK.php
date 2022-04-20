<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'hk',
    'alpha_2' => 'hk',
    'alpha_3' => 'hkg',
    'name' => 'Hong Kong',
    'currencies' => [
        CurrencyCode::HKD,
        CurrencyCode::USD
    ],
    'banks' => \HitPay\Data\Countries\HK::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\HK::paymentProviders()->toArray(),
];
