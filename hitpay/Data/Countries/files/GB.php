<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'gb',
    'alpha_2' => 'gb',
    'alpha_3' => 'gbr',
    'name' => 'United Kingdom',
    'currencies' => [
        CurrencyCode::GBP,
        CurrencyCode::EUR,
        CurrencyCode::USD,
        CurrencyCode::SEK,
        CurrencyCode::NOK,
        CurrencyCode::DKK,
        CurrencyCode::CHF,
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
    'banks' => \HitPay\Data\Countries\GB::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\GB::paymentProviders()->toArray(),
];
