<?php

namespace App\Manager;

use App\Enumerations\Business\PaymentMethodType;
use App\Manager\Paynow\PaynowManager;
use App\Manager\Stripe\PaymentIntentManager;
use App\Manager\Stripe\SourceAlipayManager;
use App\Manager\Stripe\SourceManager;
use App\Manager\Shopee\PaymentIntentManager as ShopeePaymentIntentManager;
use App\Manager\Hoolah\PaymentIntentManager as HoolahPaymentIntentManager;
use App\Manager\GrabPay\PaymentIntentManager as GrabPayPaymentIntentManager;
use App\Manager\Zip\PaymentIntentManager as ZipPaymentIntentManager;
use Exception;

class FactoryPaymentIntentManager implements FactoryPaymentIntentManagerInterface
{
    protected static $instance;

    protected function __construct()
    {
        //
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function create(string $method) : PaymentIntentManagerInterface
    {
        switch ($method) {
            case PaymentMethodType::PAYNOW:
                return new PaynowManager($method);
            case PaymentMethodType::CARD:
            case PaymentMethodType::CARD_PRESENT:
            case PaymentMethodType::GRABPAY: // GrabPay via Stripe
                return new PaymentIntentManager($method);
            case PaymentMethodType::ALIPAY:
                return new SourceAlipayManager($method);
            case PaymentMethodType::WECHAT:
                return new SourceManager($method);
            case PaymentMethodType::SHOPEE:
                return new ShopeePaymentIntentManager($method);
            case PaymentMethodType::HOOLAH:
                return new HoolahPaymentIntentManager($method);
            case PaymentMethodType::GRABPAY_DIRECT: // New direct GrabPay
            case PaymentMethodType::GRABPAY_PAYLATER: 
                return new GrabPayPaymentIntentManager($method);
            case PaymentMethodType::ZIP: 
                return new ZipPaymentIntentManager($method);  
            default:
                throw new Exception('Invalid payment method requested.');
        }
    }
}
