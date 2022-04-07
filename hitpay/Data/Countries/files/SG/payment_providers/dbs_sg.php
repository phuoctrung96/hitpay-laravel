<?php

use App\Enumerations\CurrencyCode;
use App\Enumerations\PaymentProvider;

return [
    'code' => PaymentProvider::DBS_SINGAPORE,
    'official_code' => 'dbs',
    'name' => 'DBS Bank',
    'currencies' => [
        [ 'code' => CurrencyCode::SGD, 'minimum_amount' => 50 ],
    ],
    'methods' => [
        [
            'code' => 'paynow_online',
            'currencies' => true,
        ],
        [
            'code' => 'direct_debit',
            'currencies' => true,
        ],
    ],
];
