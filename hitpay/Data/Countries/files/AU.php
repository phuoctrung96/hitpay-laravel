<?php

use App\Enumerations\CurrencyCode;

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files/AU/banks');

$files = is_dir($pathBanks) ? File::files($pathBanks) : [];

foreach ($files as $file) {
    $banks[] = require $file->getPathname();
}

$paymentProviders = [];

$pathPaymentProviders = base_path('hitpay/Data/Countries/files/AU/payment_providers');

$files = File::files($pathPaymentProviders);

foreach ($files as $file) {
    $paymentProviders[] = require $file->getPathname();
}

return [
    'id' => 'au',
    'alpha_2' => 'au',
    'alpha_3' => 'aus',
    'name' => 'Australia',
    'default_currency' => CurrencyCode::AUD,
    'currencies' => [
        CurrencyCode::AUD,
        CurrencyCode::USD,
        CurrencyCode::NZD
    ],
    'banks' => $banks,
    'payment_providers' => $paymentProviders,
];
