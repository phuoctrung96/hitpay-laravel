<?php

$banks = [];

$pathBanks = base_path('hitpay/Data/Countries/files_test/MY/banks');

$files = File::files($pathBanks);

foreach ($files as $file) {
    $banks[] = require_once $file->getPathname();
}

return [
    'banks' => $banks,
];
