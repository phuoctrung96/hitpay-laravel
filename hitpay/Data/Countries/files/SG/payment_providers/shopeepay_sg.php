<?php

use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\CurrencyCode;
use App\Enumerations\PaymentProvider;

return [
    'code' => PaymentProvider::SHOPEE_PAY,
    'official_code' => 'shopee_pay',
    'name' => 'Shopee Pay',
    'currencies' => [
        [ 'code' => CurrencyCode::SGD, 'minimum_amount' => 50 ],
    ],
    'methods' => [
        [
            'code' => PaymentMethodType::SHOPEE,
            'currencies' => true,
        ],
    ],
];
