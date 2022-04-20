<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'mx',
    'alpha_2' => 'mx',
    'alpha_3' => 'mex',
    'name' => 'Mexico',
    'currencies' => [
        CurrencyCode::MXN
    ],
    'banks' => \HitPay\Data\Countries\MX::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\MX::paymentProviders()->toArray(),
];
