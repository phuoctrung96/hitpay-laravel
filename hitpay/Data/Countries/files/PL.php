<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'pl',
    'alpha_2' => 'pl',
    'alpha_3' => 'pol',
    'name' => 'Poland',
    'currencies' => [
        CurrencyCode::PLN,
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
        CurrencyCode::HKD,
        CurrencyCode::SGD,
        CurrencyCode::ZAR,
        CurrencyCode::HUF,
        CurrencyCode::RON,
        CurrencyCode::CZK
    ],
    'banks' => \HitPay\Data\Countries\PL::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\PL::paymentProviders()->toArray(),
];
