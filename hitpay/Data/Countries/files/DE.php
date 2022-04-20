<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'de',
    'alpha_2' => 'de',
    'alpha_3' => 'deu',
    'name' => 'Germany',
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
    'banks' => \HitPay\Data\Countries\DE::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\DE::paymentProviders()->toArray(),
];
