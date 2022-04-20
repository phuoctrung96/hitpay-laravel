<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'hr',
    'alpha_2' => 'hr',
    'alpha_3' => 'hrv',
    'name' => 'Croatia',
    'currencies' => [
        CurrencyCode::HRK,
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
    'banks' => \HitPay\Data\Countries\HR::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\HR::paymentProviders()->toArray(),
];
