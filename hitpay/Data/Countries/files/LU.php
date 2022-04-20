<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'lu',
    'alpha_2' => 'lu',
    'alpha_3' => 'lux',
    'name' => 'Luxembourg',
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
    'banks' => \HitPay\Data\Countries\LU::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\LU::paymentProviders()->toArray(),
];
