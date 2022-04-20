<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'cz',
    'alpha_2' => 'cz',
    'alpha_3' => 'cze',
    'name' => 'Czech Republic',
    'currencies' => [
        CurrencyCode::CZK,
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
        CurrencyCode::RON
    ],
    'banks' => \HitPay\Data\Countries\CZ::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\CZ::paymentProviders()->toArray(),
];
