<?php

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files_test/CA/banks');

$files = is_dir($pathBanks) ? File::files($pathBanks) : [];

foreach ($files as $file) {
    $banks[] = require $file->getPathname();
}

return [
    'banks' => $banks,
];
