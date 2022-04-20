<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'lv',
    'alpha_2' => 'lv',
    'alpha_3' => 'lva',
    'name' => 'Latvia',
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
    'banks' => \HitPay\Data\Countries\LV::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\LV::paymentProviders()->toArray(),
];
