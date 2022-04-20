<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'us',
    'alpha_2' => 'us',
    'alpha_3' => 'usa',
    'name' => 'United States',
    'currencies' => [
        CurrencyCode::USD
    ],
    'banks' => \HitPay\Data\Countries\US::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\US::paymentProviders()->toArray(),
];
