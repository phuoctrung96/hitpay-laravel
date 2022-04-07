<?php

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files_test/SG/banks');

$files = File::files($pathBanks);

foreach ($files as $file) {
    $banks[] = require_once $file->getPathname();
}

return [
    'banks' => $banks,
];
