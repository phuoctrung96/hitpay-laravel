<?php

use App\Enumerations\CurrencyCode;

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files/CA/banks');

$files = is_dir($pathBanks) ? File::files($pathBanks) : [];

foreach ($files as $file) {
    $banks[] = require $file->getPathname();
}

$paymentProviders = [];

$pathPaymentProviders = base_path('hitpay/Data/Countries/files/CA/payment_providers');

$files = File::files($pathPaymentProviders);

foreach ($files as $file) {
    $paymentProviders[] = require $file->getPathname();
}

return [
    'id' => 'ca',
    'alpha_2' => 'ca',
    'alpha_3' => 'can',
    'name' => 'Canada',
    'default_currency' => CurrencyCode::CAD,
    'currencies' => [
        CurrencyCode::CAD,
        CurrencyCode::USD
    ],
    'banks' => $banks,
    'payment_providers' => $paymentProviders,
];
