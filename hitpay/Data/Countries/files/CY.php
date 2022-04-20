<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'cy',
    'alpha_2' => 'cy',
    'alpha_3' => 'cyp',
    'name' => 'Cyprus',
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
    'banks' => \HitPay\Data\Countries\CY::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\CY::paymentProviders()->toArray(),
];
