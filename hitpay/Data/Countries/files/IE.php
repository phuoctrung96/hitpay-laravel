<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'ie',
    'alpha_2' => 'ie',
    'alpha_3' => 'irl',
    'name' => 'Ireland',
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
    'banks' => \HitPay\Data\Countries\IE::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\IE::paymentProviders()->toArray(),
];
