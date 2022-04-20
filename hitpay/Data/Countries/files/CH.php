<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'ch',
    'alpha_2' => 'ch',
    'alpha_3' => 'che',
    'name' => 'Switzerland',
    'currencies' => [
        CurrencyCode::CHF,
        CurrencyCode::EUR,
        CurrencyCode::USD,
        CurrencyCode::SEK,
        CurrencyCode::NOK,
        CurrencyCode::DKK,
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
    'banks' => \HitPay\Data\Countries\CH::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\CH::paymentProviders()->toArray(),
];
