<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'ro',
    'alpha_2' => 'ro',
    'alpha_3' => 'rou',
    'name' => 'Romania',
    'currencies' => [
        CurrencyCode::RON,
        CurrencyCode::EUR,
        CurrencyCode::USD,
        CurrencyCode::SEK,
        CurrencyCode::NOK,
        CurrencyCode::DKK,
        CurrencyCode::CHF,
        CurrencyCode::GBP,
        CurrencyCode::AUD,
        CurrencyCode::CAD,
        CurrencyCode::JPY,
        CurrencyCode::NZD,
        CurrencyCode::PLN,
        CurrencyCode::HKD,
        CurrencyCode::SGD,
        CurrencyCode::ZAR,
        CurrencyCode::HUF,
        CurrencyCode::CZK
    ],
    'banks' => \HitPay\Data\Countries\RO::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\RO::paymentProviders()->toArray(),
];
