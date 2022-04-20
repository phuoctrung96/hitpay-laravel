<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'br',
    'alpha_2' => 'br',
    'alpha_3' => 'bra',
    'name' => 'Brazil',
    'currencies' => [
        CurrencyCode::BRL
    ],
    'banks' => \HitPay\Data\Countries\BR::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\BR::paymentProviders()->toArray(),
];
