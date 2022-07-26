<?php

use App\Enumerations\CurrencyCode;

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files/LI/banks');

$files = is_dir($pathBanks) ? File::files($pathBanks) : [];

foreach ($files as $file) {
    $banks[] = require $file->getPathname();
}

$paymentProviders = [];

$pathPaymentProviders = base_path('hitpay/Data/Countries/files/LI/payment_providers');

$files = File::files($pathPaymentProviders);

foreach ($files as $file) {
    $paymentProviders[] = require $file->getPathname();
}

return [
    'id' => 'li',
    'alpha_2' => 'li',
    'alpha_3' => 'lie',
    'name' => 'Liechtenstein',
    'default_currency' => CurrencyCode::EUR,
    'currencies' => [
        CurrencyCode::EUR,
        CurrencyCode::USD,
        CurrencyCode::CHF,
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
    'banks' => $banks,
    'payment_providers' => $paymentProviders,
];
