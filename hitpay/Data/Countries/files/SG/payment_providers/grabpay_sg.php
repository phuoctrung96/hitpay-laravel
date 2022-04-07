<?php

use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\CurrencyCode;
use App\Enumerations\PaymentProvider;

return [
    'code' => PaymentProvider::GRABPAY,
    'official_code' => 'grabpay',
    'name' => 'GrabPay',
    'currencies' => [
        [ 'code' => CurrencyCode::SGD, 'minimum_amount' => 50 ],
    ],
    'methods' => [
        [
            'code' => PaymentMethodType::GRABPAY_DIRECT,
            'currencies' => true,
        ],
        [
            'code' => PaymentMethodType::GRABPAY_PAYLATER,
            'currencies' => true,
        ],
    ],
];
