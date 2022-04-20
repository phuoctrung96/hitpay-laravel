<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'es',
    'alpha_2' => 'es',
    'alpha_3' => 'esp',
    'name' => 'Spain',
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
    'banks' => \HitPay\Data\Countries\ES::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\ES::paymentProviders()->toArray(),
];
