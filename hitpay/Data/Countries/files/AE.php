<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'ae',
    'alpha_2' => 'ae',
    'alpha_3' => 'are',
    'name' => 'United Arab Emirates',
    'currencies' => [
        CurrencyCode::AED
    ],
    'banks' => \HitPay\Data\Countries\AE::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\AE::paymentProviders()->toArray(),
];
