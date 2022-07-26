<?php

use App\Enumerations\CurrencyCode;

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files/MX/banks');

$files = is_dir($pathBanks) ? File::files($pathBanks) : [];

foreach ($files as $file) {
    $banks[] = require $file->getPathname();
}

$paymentProviders = [];

$pathPaymentProviders = base_path('hitpay/Data/Countries/files/MX/payment_providers');

$files = File::files($pathPaymentProviders);

foreach ($files as $file) {
    $paymentProviders[] = require $file->getPathname();
}

return [
    'id' => 'mx',
    'alpha_2' => 'mx',
    'alpha_3' => 'mex',
    'name' => 'Mexico',
    'default_currency' => CurrencyCode::MXN,
    'currencies' => [
        CurrencyCode::MXN
    ],
    'banks' => $banks,
    'payment_providers' => $paymentProviders,
];
