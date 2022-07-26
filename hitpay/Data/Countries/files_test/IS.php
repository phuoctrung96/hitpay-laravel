<?php

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files_test/IS/banks');

$files = is_dir($pathBanks) ? File::files($pathBanks) : [];

foreach ($files as $file) {
    $banks[] = require $file->getPathname();
}

return [
    'banks' => $banks,
];
