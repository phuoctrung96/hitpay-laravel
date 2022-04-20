<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'it',
    'alpha_2' => 'it',
    'alpha_3' => 'ita',
    'name' => 'Italy',
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
    'banks' => \HitPay\Data\Countries\IT::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\IT::paymentProviders()->toArray(),
];
