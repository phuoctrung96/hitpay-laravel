<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'pt',
    'alpha_2' => 'pt',
    'alpha_3' => 'prt',
    'name' => 'Portugal',
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
    'banks' => \HitPay\Data\Countries\PT::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\PT::paymentProviders()->toArray(),
];
