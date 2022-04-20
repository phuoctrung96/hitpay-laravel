<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'no',
    'alpha_2' => 'no',
    'alpha_3' => 'nor',
    'name' => 'Norway',
    'currencies' => [
        CurrencyCode::NOK,
        CurrencyCode::EUR,
        CurrencyCode::USD,
        CurrencyCode::SEK,
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
        CurrencyCode::RON,
        CurrencyCode::CZK
    ],
    'banks' => \HitPay\Data\Countries\NO::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\NO::paymentProviders()->toArray(),
];
