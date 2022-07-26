<?php

use App\Enumerations\CurrencyCode;

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files/RO/banks');

$files = is_dir($pathBanks) ? File::files($pathBanks) : [];

foreach ($files as $file) {
    $banks[] = require $file->getPathname();
}

$paymentProviders = [];

$pathPaymentProviders = base_path('hitpay/Data/Countries/files/RO/payment_providers');

$files = File::files($pathPaymentProviders);

foreach ($files as $file) {
    $paymentProviders[] = require $file->getPathname();
}

return [
    'id' => 'ro',
    'alpha_2' => 'ro',
    'alpha_3' => 'rou',
    'name' => 'Romania',
    'default_currency' => CurrencyCode::RON,
    'currencies' => [
        CurrencyCode::RON,
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
        CurrencyCode::CZK
    ],
    'banks' => $banks,
    'payment_providers' => $paymentProviders,
];
