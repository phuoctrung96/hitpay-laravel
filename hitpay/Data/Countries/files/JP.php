<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'jp',
    'alpha_2' => 'jp',
    'alpha_3' => 'jpn',
    'name' => 'Japan',
    'currencies' => [
        CurrencyCode::JPY
    ],
    'banks' => \HitPay\Data\Countries\JP::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\JP::paymentProviders()->toArray(),
];
