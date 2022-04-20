<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'dk',
    'alpha_2' => 'dk',
    'alpha_3' => 'dnk',
    'name' => 'Denmark',
    'currencies' => [
        CurrencyCode::DKK,
        CurrencyCode::EUR,
        CurrencyCode::USD,
        CurrencyCode::SEK,
        CurrencyCode::NOK,
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
    'banks' => \HitPay\Data\Countries\DK::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\DK::paymentProviders()->toArray(),
];
