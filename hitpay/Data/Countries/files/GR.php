<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => 'gr',
    'alpha_2' => 'gr',
    'alpha_3' => 'grc',
    'name' => 'Greece',
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
    'banks' => \HitPay\Data\Countries\GR::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\GR::paymentProviders()->toArray(),
];
