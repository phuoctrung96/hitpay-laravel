<?php

use App\Enumerations\CurrencyCode;

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files/NZ/banks');

$files = is_dir($pathBanks) ? File::files($pathBanks) : [];

foreach ($files as $file) {
    $banks[] = require $file->getPathname();
}

$paymentProviders = [];

$pathPaymentProviders = base_path('hitpay/Data/Countries/files/NZ/payment_providers');

$files = File::files($pathPaymentProviders);

foreach ($files as $file) {
    $paymentProviders[] = require $file->getPathname();
}

return [
    'id' => 'nz',
    'alpha_2' => 'nz',
    'alpha_3' => 'nzl',
    'name' => 'New Zealand',
    'default_currency' => CurrencyCode::NZD,
    'currencies' => [
        CurrencyCode::NZD
    ],
    'banks' => $banks,
    'payment_providers' => $paymentProviders,
];
