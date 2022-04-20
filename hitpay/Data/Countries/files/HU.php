<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'hu',
    'alpha_2' => 'hu',
    'alpha_3' => 'hun',
    'name' => 'Hungary',
    'currencies' => [
        CurrencyCode::HUF,
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
        CurrencyCode::RON,
        CurrencyCode::CZK
    ],
    'banks' => \HitPay\Data\Countries\HU::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\HU::paymentProviders()->toArray(),
];
