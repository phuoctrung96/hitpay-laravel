<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'bg',
    'alpha_2' => 'bg',
    'alpha_3' => 'bgr',
    'name' => 'Bulgaria',
    'currencies' => [
        CurrencyCode::BGN,
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
    'banks' => \HitPay\Data\Countries\BG::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\BG::paymentProviders()->toArray(),
];
