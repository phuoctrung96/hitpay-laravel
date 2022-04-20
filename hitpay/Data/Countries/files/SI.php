<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'si',
    'alpha_2' => 'si',
    'alpha_3' => 'svn',
    'name' => 'Slovenia',
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
    'banks' => \HitPay\Data\Countries\SI::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\SI::paymentProviders()->toArray(),
];
