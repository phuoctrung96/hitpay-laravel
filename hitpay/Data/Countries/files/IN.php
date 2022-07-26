<?php

use App\Enumerations\CurrencyCode;

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files/IN/banks');

$files = is_dir($pathBanks) ? File::files($pathBanks) : [];

foreach ($files as $file) {
    $banks[] = require $file->getPathname();
}

$paymentProviders = [];

$pathPaymentProviders = base_path('hitpay/Data/Countries/files/IN/payment_providers');

$files = File::files($pathPaymentProviders);

foreach ($files as $file) {
    $paymentProviders[] = require $file->getPathname();
}

return [
    'id' => 'in',
    'alpha_2' => 'in',
    'alpha_3' => 'ind',
    'name' => 'India',
    'default_currency' => CurrencyCode::INR,
    'currencies' => [
        CurrencyCode::INR
    ],
    'banks' => $banks,
    'payment_providers' => $paymentProviders,
];
