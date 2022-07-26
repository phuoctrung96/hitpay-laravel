<?php

use App\Enumerations\CurrencyCode;

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files/HK/banks');

$files = is_dir($pathBanks) ? File::files($pathBanks) : [];

foreach ($files as $file) {
    $banks[] = require $file->getPathname();
}

$paymentProviders = [];

$pathPaymentProviders = base_path('hitpay/Data/Countries/files/HK/payment_providers');

$files = File::files($pathPaymentProviders);

foreach ($files as $file) {
    $paymentProviders[] = require $file->getPathname();
}

return [
    'id' => 'hk',
    'alpha_2' => 'hk',
    'alpha_3' => 'hkg',
    'name' => 'Hong Kong',
    'default_currency' => CurrencyCode::HKD,
    'currencies' => [
        CurrencyCode::HKD,
        CurrencyCode::USD
    ],
    'banks' => $banks,
    'payment_providers' => $paymentProviders,
];
