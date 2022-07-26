<?php

use App\Enumerations\CurrencyCode;

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files/BR/banks');

$files = is_dir($pathBanks) ? File::files($pathBanks) : [];

foreach ($files as $file) {
    $banks[] = require $file->getPathname();
}

$paymentProviders = [];

$pathPaymentProviders = base_path('hitpay/Data/Countries/files/BR/payment_providers');

$files = File::files($pathPaymentProviders);

foreach ($files as $file) {
    $paymentProviders[] = require $file->getPathname();
}

return [
    'id' => 'br',
    'alpha_2' => 'br',
    'alpha_3' => 'bra',
    'name' => 'Brazil',
    'default_currency' => CurrencyCode::BRL,
    'currencies' => [
        CurrencyCode::BRL
    ],
    'banks' => $banks,
    'payment_providers' => $paymentProviders,
];
