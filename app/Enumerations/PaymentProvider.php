<?php

namespace App\Enumerations;

class PaymentProvider extends Enumeration
{
    const STRIPE_MALAYSIA = 'stripe_my';
    const STRIPE_SINGAPORE = 'stripe_sg';
    const STRIPE_US = 'stripe_us';
    const DBS_SINGAPORE = 'dbs_sg';
    const SHOPEE_PAY = 'shopee_pay';
    const HOOLAH = 'hoolah';
    const GRABPAY = 'grabpay';
    const ZIP = 'zip';

    public static function displayName ($provider) {
      $names = [
        self::STRIPE_MALAYSIA  => 'Stripe (Malaysia)',
        self::STRIPE_SINGAPORE => 'Stripe (Singapore)',
        self::STRIPE_US        => 'Stripe (US)',
        self::DBS_SINGAPORE    => 'PayNow',
        self::SHOPEE_PAY       => 'Shopee Pay',
        self::HOOLAH           => 'Hoolah',
        self::GRABPAY          => 'GrabPay',
        self::ZIP              => 'Zip',
      ];

      return $names[$provider];
    }
}
