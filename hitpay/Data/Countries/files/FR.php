<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'fr',
    'alpha_2' => 'fr',
    'alpha_3' => 'fra',
    'name' => 'France',
    'currencies' => [
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
        CurrencyCode::RON,
        CurrencyCode::CZK
    ],
    'banks' => \HitPay\Data\Countries\FR::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\FR::paymentProviders()->toArray(),
];
