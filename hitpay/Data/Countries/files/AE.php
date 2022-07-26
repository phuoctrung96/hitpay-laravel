<?php

use App\Enumerations\CurrencyCode;

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files/AE/banks');

$files = is_dir($pathBanks) ? File::files($pathBanks) : [];

foreach ($files as $file) {
    $banks[] = require $file->getPathname();
}

$paymentProviders = [];

$pathPaymentProviders = base_path('hitpay/Data/Countries/files/AE/payment_providers');

$files = File::files($pathPaymentProviders);

foreach ($files as $file) {
    $paymentProviders[] = require $file->getPathname();
}

return [
    'id' => 'ae',
    'alpha_2' => 'ae',
    'alpha_3' => 'are',
    'name' => 'United Arab Emirates',
    'default_currency' => CurrencyCode::AED,
    'currencies' => [
        CurrencyCode::AED
    ],
    'banks' => $banks,
    'payment_providers' => $paymentProviders,
];
