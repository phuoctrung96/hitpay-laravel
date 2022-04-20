<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'in',
    'alpha_2' => 'in',
    'alpha_3' => 'ind',
    'name' => 'India',
    'currencies' => [
        CurrencyCode::INR
    ],
    'banks' => \HitPay\Data\Countries\IN::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\IN::paymentProviders()->toArray(),
];
