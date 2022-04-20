<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'mt',
    'alpha_2' => 'mt',
    'alpha_3' => 'mlt',
    'name' => 'Malta',
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
    'banks' => \HitPay\Data\Countries\MT::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\MT::paymentProviders()->toArray(),
];
