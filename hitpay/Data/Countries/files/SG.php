<?php

use App\Enumerations\CurrencyCode;

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files/SG/banks');

$files = is_dir($pathBanks) ? File::files($pathBanks) : [];

foreach ($files as $file) {
    $banks[] = require $file->getPathname();
}

$paymentProviders = [];

$pathPaymentProviders = base_path('hitpay/Data/Countries/files/SG/payment_providers');

$files = File::files($pathPaymentProviders);

foreach ($files as $file) {
    $paymentProviders[] = require $file->getPathname();
}

return [
    'id' => 'sg',
    'alpha_2' => 'sg',
    'alpha_3' => 'sgp',
    'name' => 'Singapore',
    'default_currency' => CurrencyCode::SGD,
    'currencies' => [
        CurrencyCode::SGD,
        CurrencyCode::USD
    ],
    'banks' => $banks,
    'payment_providers' => $paymentProviders,
];
