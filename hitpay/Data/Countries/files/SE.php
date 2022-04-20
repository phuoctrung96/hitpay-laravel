<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'se',
    'alpha_2' => 'se',
    'alpha_3' => 'swe',
    'name' => 'Sweden',
    'currencies' => [
        CurrencyCode::SEK,
        CurrencyCode::EUR,
        CurrencyCode::USD,
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
    'banks' => \HitPay\Data\Countries\SE::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\SE::paymentProviders()->toArray(),
];
