<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'nl',
    'alpha_2' => 'nl',
    'alpha_3' => 'nld',
    'name' => 'Netherlands',
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
    'banks' => \HitPay\Data\Countries\NL::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\NL::paymentProviders()->toArray(),
];
