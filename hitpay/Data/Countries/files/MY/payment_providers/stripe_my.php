<?php

use App\Enumerations\CurrencyCode;
use App\Enumerations\PaymentProvider;

return [
    'code' => PaymentProvider::STRIPE_MALAYSIA,
    'official_code' => 'stripe',
    'name' => 'Stripe Malaysia',
    'currencies' => [
        [ 'code' => CurrencyCode::AED, 'minimum_amount' => 200 ],
        [ 'code' => CurrencyCode::ALL ],
        [ 'code' => CurrencyCode::AMD ],
        [ 'code' => CurrencyCode::ANG ],
        [ 'code' => CurrencyCode::AOA ],
        [ 'code' => CurrencyCode::ARS ],
        [ 'code' => CurrencyCode::AUD, 'minimum_amount' => 50 ],
        [ 'code' => CurrencyCode::AWG ],
        [ 'code' => CurrencyCode::AZN ],
        [ 'code' => CurrencyCode::BAM ],
        [ 'code' => CurrencyCode::BBD ],
        [ 'code' => CurrencyCode::BDT ],
        [ 'code' => CurrencyCode::BGN, 'minimum_amount' => 100 ],
        [ 'code' => CurrencyCode::BIF, 'minimum_amount' => 1100 ],
        [ 'code' => CurrencyCode::BMD ],
        [ 'code' => CurrencyCode::BND ],
        [ 'code' => CurrencyCode::BOB ],
        [ 'code' => CurrencyCode::BRL, 'minimum_amount' => 50 ],
        [ 'code' => CurrencyCode::BSD ],
        [ 'code' => CurrencyCode::BWP ],
        [ 'code' => CurrencyCode::BZD ],
        [ 'code' => CurrencyCode::CAD, 'minimum_amount' => 50 ],
        [ 'code' => CurrencyCode::CDF ],
        [ 'code' => CurrencyCode::CHF, 'minimum_amount' => 50 ],
        [ 'code' => CurrencyCode::CLP, 'minimum_amount' => 500 ],
        [ 'code' => CurrencyCode::CNY ],
        [ 'code' => CurrencyCode::COP ],
        [ 'code' => CurrencyCode::CRC ],
        [ 'code' => CurrencyCode::CVE ],
        [ 'code' => CurrencyCode::CZK, 'minimum_amount' => 1500 ],
        [ 'code' => CurrencyCode::DJF, 'minimum_amount' => 100 ],
        [ 'code' => CurrencyCode::DKK, 'minimum_amount' => 250 ],
        [ 'code' => CurrencyCode::DOP ],
        [ 'code' => CurrencyCode::DZD ],
        [ 'code' => CurrencyCode::EGP ],
        [ 'code' => CurrencyCode::ETB ],
        [ 'code' => CurrencyCode::EUR, 'minimum_amount' => 50 ],
        [ 'code' => CurrencyCode::FJD ],
        [ 'code' => CurrencyCode::FKP ],
        [ 'code' => CurrencyCode::GBP, 'minimum_amount' => 30 ],
        [ 'code' => CurrencyCode::GEL ],
        [ 'code' => CurrencyCode::GIP ],
        [ 'code' => CurrencyCode::GMD ],
        [ 'code' => CurrencyCode::GNF, 'minimum_amount' => 5000 ],
        [ 'code' => CurrencyCode::GTQ ],
        [ 'code' => CurrencyCode::GYD ],
        [ 'code' => CurrencyCode::HKD, 'minimum_amount' => 400 ],
        [ 'code' => CurrencyCode::HNL ],
        [ 'code' => CurrencyCode::HRK ],
        [ 'code' => CurrencyCode::HTG ],
        [ 'code' => CurrencyCode::HUF, 'minimum_amount' => 17500 ],
        [ 'code' => CurrencyCode::IDR, 'minimum_amount' => 1000000 ],
        [ 'code' => CurrencyCode::ILS ],
        [ 'code' => CurrencyCode::INR, 'minimum_amount' => 50 ],
        [ 'code' => CurrencyCode::ISK ],
        [ 'code' => CurrencyCode::JMD ],
        [ 'code' => CurrencyCode::JPY, 'minimum_amount' => 50 ],
        [ 'code' => CurrencyCode::KES ],
        [ 'code' => CurrencyCode::KGS ],
        [ 'code' => CurrencyCode::KHR ],
        [ 'code' => CurrencyCode::KMF, 'minimum_amount' => 300 ],
        [ 'code' => CurrencyCode::KRW, 'minimum_amount' => 800 ],
        [ 'code' => CurrencyCode::KYD ],
        [ 'code' => CurrencyCode::KZT ],
        [ 'code' => CurrencyCode::LAK ],
        [ 'code' => CurrencyCode::LBP ],
        [ 'code' => CurrencyCode::LKR ],
        [ 'code' => CurrencyCode::LRD ],
        [ 'code' => CurrencyCode::LSL ],
        [ 'code' => CurrencyCode::MAD ],
        [ 'code' => CurrencyCode::MDL ],
        [ 'code' => CurrencyCode::MGA, 'minimum_amount' => 2500 ],
        [ 'code' => CurrencyCode::MKD ],
        [ 'code' => CurrencyCode::MMK ],
        [ 'code' => CurrencyCode::MNT ],
        [ 'code' => CurrencyCode::MOP ],
        [ 'code' => CurrencyCode::MRO ],
        [ 'code' => CurrencyCode::MUR ],
        [ 'code' => CurrencyCode::MVR ],
        [ 'code' => CurrencyCode::MWK ],
        [ 'code' => CurrencyCode::MXN, 'minimum_amount' => 1000 ],
        [ 'code' => CurrencyCode::MYR, 'minimum_amount' => 200 ],
        [ 'code' => CurrencyCode::MZN ],
        [ 'code' => CurrencyCode::NAD ],
        [ 'code' => CurrencyCode::NGN ],
        [ 'code' => CurrencyCode::NIO ],
        [ 'code' => CurrencyCode::NOK, 'minimum_amount' => 300 ],
        [ 'code' => CurrencyCode::NPR ],
        [ 'code' => CurrencyCode::NZD, 'minimum_amount' => 50 ],
        [ 'code' => CurrencyCode::PAB ],
        [ 'code' => CurrencyCode::PEN ],
        [ 'code' => CurrencyCode::PGK ],
        [ 'code' => CurrencyCode::PHP ],
        [ 'code' => CurrencyCode::PKR ],
        [ 'code' => CurrencyCode::PLN, 'minimum_amount' => 200 ],
        [ 'code' => CurrencyCode::PYG, 'minimum_amount' => 4000 ],
        [ 'code' => CurrencyCode::QAR ],
        [ 'code' => CurrencyCode::RON, 'minimum_amount' => 200 ],
        [ 'code' => CurrencyCode::RSD ],
        [ 'code' => CurrencyCode::RUB ],
        [ 'code' => CurrencyCode::RWF, 'minimum_amount' => 1000 ],
        [ 'code' => CurrencyCode::SAR ],
        [ 'code' => CurrencyCode::SBD ],
        [ 'code' => CurrencyCode::SCR ],
        [ 'code' => CurrencyCode::SEK, 'minimum_amount' => 300 ],
        [ 'code' => CurrencyCode::SGD, 'minimum_amount' => 50 ],
        [ 'code' => CurrencyCode::SHP ],
        [ 'code' => CurrencyCode::SLL ],
        [ 'code' => CurrencyCode::SOS ],
        [ 'code' => CurrencyCode::SRD ],
        [ 'code' => CurrencyCode::STD ],
        [ 'code' => CurrencyCode::SZL ],
        [ 'code' => CurrencyCode::THB ],
        [ 'code' => CurrencyCode::TJS ],
        [ 'code' => CurrencyCode::TOP ],
        [ 'code' => CurrencyCode::TRY ],
        [ 'code' => CurrencyCode::TTD ],
        [ 'code' => CurrencyCode::TWD ],
        [ 'code' => CurrencyCode::TZS ],
        [ 'code' => CurrencyCode::UAH ],
        [ 'code' => CurrencyCode::UGX, 'minimum_amount' => 3000 ],
        [ 'code' => CurrencyCode::USD, 'minimum_amount' => 50 ],
        [ 'code' => CurrencyCode::UYU ],
        [ 'code' => CurrencyCode::UZS ],
        [ 'code' => CurrencyCode::VND, 'minimum_amount' => 15000 ],
        [ 'code' => CurrencyCode::VUV, 'minimum_amount' => 100 ],
        [ 'code' => CurrencyCode::WST ],
        [ 'code' => CurrencyCode::XAF, 'minimum_amount' => 500 ],
        [ 'code' => CurrencyCode::XCD ],
        [ 'code' => CurrencyCode::XOF, 'minimum_amount' => 500 ],
        [ 'code' => CurrencyCode::XPF, 'minimum_amount' => 100 ],
        [ 'code' => CurrencyCode::YER ],
        [ 'code' => CurrencyCode::ZAR ],
        [ 'code' => CurrencyCode::ZMW ],
    ],
    'methods' => [
        [
            'code' => 'alipay',
            'currencies' => [
                CurrencyCode::MYR,
            ],
        ],
        [
            'code' => 'card',
            'currencies' => true,
        ],
        [
            'code' => 'fpx',
            'currencies' => [
                CurrencyCode::MYR,
            ],
        ],
        [
            'code' => 'grabpay',
            'currencies' => [
                CurrencyCode::MYR,
            ],
        ],
    ],
];
