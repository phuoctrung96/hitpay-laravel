<?php

use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\CurrencyCode;
use App\Enumerations\PaymentProvider;

return [
    'code' => PaymentProvider::ZIP,
    'official_code' => 'zip',
    'name' => 'Zip',
    'currencies' => [
        [ 'code' => CurrencyCode::SGD, 'minimum_amount' => 50 ],
    ],
    'methods' => [
        [
            'code' => PaymentMethodType::ZIP,
            'currencies' => true,
        ],
    ],
];
