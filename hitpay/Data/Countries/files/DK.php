<?php

use App\Enumerations\CurrencyCode;

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files/DK/banks');

$files = is_dir($pathBanks) ? File::files($pathBanks) : [];

foreach ($files as $file) {
    $banks[] = require $file->getPathname();
}

$paymentProviders = [];

$pathPaymentProviders = base_path('hitpay/Data/Countries/files/DK/payment_providers');

$files = File::files($pathPaymentProviders);

foreach ($files as $file) {
    $paymentProviders[] = require $file->getPathname();
}

return [
    'id' => 'dk',
    'alpha_2' => 'dk',
    'alpha_3' => 'dnk',
    'name' => 'Denmark',
    'default_currency' => CurrencyCode::DKK,
    'currencies' => [
        CurrencyCode::DKK,
        CurrencyCode::EUR,
        CurrencyCode::USD,
        CurrencyCode::SEK,
        CurrencyCode::NOK,
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
    'banks' => $banks,
    'payment_providers' => $paymentProviders,
];
