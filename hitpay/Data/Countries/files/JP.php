<?php

use App\Enumerations\CurrencyCode;

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files/JP/banks');

$files = is_dir($pathBanks) ? File::files($pathBanks) : [];

foreach ($files as $file) {
    $banks[] = require $file->getPathname();
}

$paymentProviders = [];

$pathPaymentProviders = base_path('hitpay/Data/Countries/files/JP/payment_providers');

$files = File::files($pathPaymentProviders);

foreach ($files as $file) {
    $paymentProviders[] = require $file->getPathname();
}

return [
    'id' => 'jp',
    'alpha_2' => 'jp',
    'alpha_3' => 'jpn',
    'name' => 'Japan',
    'default_currency' => CurrencyCode::JPY,
    'currencies' => [
        CurrencyCode::JPY
    ],
    'banks' => $banks,
    'payment_providers' => $paymentProviders,
];
