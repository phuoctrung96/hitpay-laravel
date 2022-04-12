<?php

namespace App\Enumerations\Business;

use App\Business;
use App\Business\PaymentProvider;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;

class PaymentMethodType
{
    const CASH              = 'cash';
    const CARD              = 'card';
    const COLLECTION        = 'collection';
    const DIRECT_DEBIT      = 'direct_debit';
    const FPX               = 'fpx';
    const ALIPAY            = 'alipay';
    const WECHAT            = 'wechat';
    const CARD_PRESENT      = 'card_present';
    const PAYNOW            = 'paynow_online';
    const GRABPAY           = 'grabpay'; // Old GrabPay via Stripe
    const GRABPAY_DIRECT    = 'grabpay_direct';
    const GRABPAY_PAYLATER  = 'grabpay_paylater';
    const SHOPEE            = 'shopee_pay';
    const HOOLAH            = 'hoolah';
    const ZIP               = 'zip';
    const GIRO              = 'giro';

    public static function getPaymentMethods()
    {
        return [
            self::CASH,
            self::CARD,
            self::ALIPAY,
            self::WECHAT,
            self::CARD_PRESENT,
            self::PAYNOW,
            self::GRABPAY,
            self::SHOPEE,
            self::HOOLAH,
            self::ZIP
        ];
    }

    public static function getPaymentMethodsSg()
    {
        return [
            self::CARD,
            self::PAYNOW,
            self::GRABPAY,
            self::ZIP,
            self::SHOPEE
        ];
    }

    public static function getPaymentMethodsMy()
    {
        return [
            self::CARD,
            self::FPX
        ];
    }

    public static function displayName ($method) {
      $names = [
        self::CASH             => 'Cash',
        self::CARD             => 'Charge Card',
        self::COLLECTION       => 'Collection',
        self::ALIPAY           => 'Alipay',
        self::WECHAT           => 'WeChat Pay',
        self::CARD_PRESENT     => 'Card Reader',
        self::PAYNOW           => 'Paynow Online',
        self::GRABPAY          => 'GrabPay',
        self::GRABPAY_DIRECT   => 'GrabPay (direct)',
        self::GRABPAY_PAYLATER => 'GrabPay PayLater',
        self::SHOPEE           => 'Shopee Pay',
        self::HOOLAH           => 'Hoolah',
        'direct_debit'         => 'Direct Debit',
        self::ZIP              => 'Zip',
          self::FPX            => 'FPX',
      ];

      $icon = 'fas fa-dollar-sign';

      switch ($method) {
        case self::CARD:
          $icon = 'far fa-credit-card';
          break;

        case self::ALIPAY:
          $icon = 'fab fa-alipay';
          break;

        case self::WECHAT:
          $icon = 'fab fa-weixin';
          break;

        case self::CARD_PRESENT:
          $icon = 'fas fa-calculator';
          break;
      }

      $name = $method ? $names[$method] : 'Unknown';

      return '<i class="' . $icon . '"></i> ' . $name;
    }
}
