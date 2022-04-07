<?php

use App\Enumerations\CurrencyCode;

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files/SG/banks');

$files = File::files($pathBanks);

foreach ($files as $file) {
    $banks[] = require_once $file->getPathname();
}

$paymentProviders = [];

$pathPaymentProviders = base_path('hitpay/Data/Countries/files/SG/payment_providers');

$files = File::files($pathPaymentProviders);

foreach ($files as $file) {
    $paymentProviders[] = require_once $file->getPathname();
}

return [
    'id' => 'sg',
    'alpha_2' => 'sg',
    'alpha_3' => 'sgp',
    'name' => 'Singapore',
    'currencies' => [
        CurrencyCode::SGD,
    ],
    'banks' => $banks,
    'payment_providers' => $paymentProviders,
];
