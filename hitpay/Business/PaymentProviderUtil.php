<?php

namespace HitPay\Business;

use App\Enumerations\OnboardingStatus;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Enumerations\Business\PaymentMethodType;
use App\Business\PaymentProvider;

trait PaymentProviderUtil
{
  // May be also need to pass country here
  static function getProviderForMethod ($business, $method) {
    $providerSlug = [
      PaymentMethodType::PAYNOW => PaymentProviderEnum::DBS_SINGAPORE,
      // Card options
      PaymentMethodType::CARD => $business->payment_provider,
      PaymentMethodType::CARD_PRESENT => $business->payment_provider,
      PaymentMethodType::GRABPAY => $business->payment_provider, // Old GrabPay via Stripe
      // AliPay & WeChay
      PaymentMethodType::ALIPAY => $business->payment_provider,
      PaymentMethodType::WECHAT => $business->payment_provider,

      // FPX
      PaymentMethodType::FPX => $business->payment_provider,

      // GrabPay 
      PaymentMethodType::GRABPAY_DIRECT => PaymentProviderEnum::GRABPAY,
      PaymentMethodType::GRABPAY_PAYLATER => PaymentProviderEnum::GRABPAY,

      // Shopee
      PaymentMethodType::SHOPEE => PaymentProviderEnum::SHOPEE_PAY,

      // Hoolah
      PaymentMethodType::HOOLAH => PaymentProviderEnum::HOOLAH,

      // Zip
      PaymentMethodType::ZIP => PaymentProviderEnum::ZIP,
    ];

    $providersCheckStatus = [
      PaymentProviderEnum::GRABPAY,
      PaymentProviderEnum::SHOPEE_PAY,
      PaymentProviderEnum::HOOLAH,
      PaymentProviderEnum::ZIP
    ];

    if (array_key_exists($method, $providerSlug)) {
      $slug = $providerSlug[$method];

      $provider = PaymentProvider::where([
        'business_id' => $business->id,
        'payment_provider' => $slug
      ]);

      if (in_array($slug, $providersCheckStatus)) {
        $provider = $provider->whereIn('onboarding_status', ['', OnboardingStatus::SUCCESS]);
      }

      return $provider->first();
    } else {
      return null;
    }
  }
}