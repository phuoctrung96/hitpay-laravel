<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'nz',
    'alpha_2' => 'nz',
    'alpha_3' => 'nzl',
    'name' => 'New Zealand',
    'currencies' => [
        CurrencyCode::NZD
    ],
    'banks' => \HitPay\Data\Countries\NZ::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\NZ::paymentProviders()->toArray(),
];
