<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'lt',
    'alpha_2' => 'lt',
    'alpha_3' => 'ltu',
    'name' => 'Lithuania',
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
    'banks' => \HitPay\Data\Countries\LT::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\LT::paymentProviders()->toArray(),
];
