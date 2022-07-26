<?php

use App\Enumerations\CurrencyCode;

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files/US/banks');

$files = is_dir($pathBanks) ? File::files($pathBanks) : [];

foreach ($files as $file) {
    $banks[] = require $file->getPathname();
}

$paymentProviders = [];

$pathPaymentProviders = base_path('hitpay/Data/Countries/files/US/payment_providers');

$files = File::files($pathPaymentProviders);

foreach ($files as $file) {
    $paymentProviders[] = require $file->getPathname();
}

return [
    'id' => 'us',
    'alpha_2' => 'us',
    'alpha_3' => 'usa',
    'name' => 'United States',
    'default_currency' => CurrencyCode::USD,
    'currencies' => [
        CurrencyCode::USD
    ],
    'banks' => $banks,
    'payment_providers' => $paymentProviders,
];
