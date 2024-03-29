<?php

use App\Enumerations\CurrencyCode;

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files/MY/banks');

$files = is_dir($pathBanks) ? File::files($pathBanks) : [];

foreach ($files as $file) {
    $banks[] = require $file->getPathname();
}

$paymentProviders = [];

$pathPaymentProviders = base_path('hitpay/Data/Countries/files/MY/payment_providers');

$files = File::files($pathPaymentProviders);

foreach ($files as $file) {
    $paymentProviders[] = require $file->getPathname();
}

return [
    'id' => 'my',
    'alpha_2' => 'my',
    'alpha_3' => 'mys',
    'name' => 'Malaysia',
    'default_currency' => CurrencyCode::MYR,
    'currencies' => [
        CurrencyCode::MYR
    ],
    'banks' => $banks,
    'payment_providers' => $paymentProviders,
];
