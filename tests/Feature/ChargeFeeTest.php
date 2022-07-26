<?php

namespace Tests\Feature;

use App\Business;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\CurrencyCode;
use HitPay\Test\Objects\ChargeConfig;
use Tests\TestCase;

class ChargeFeeTest extends TestCase
{
    public function testMalaysiaStripePaymentProvider()
    {
        $paymentProvider = Business\PaymentProvider::query()->whereDoesntHave('rates')->where([
            'payment_provider' => 'stripe_my',
        ])->latest()->first();

        $this->doTestChargeFee($paymentProvider, [
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::MYR, 200, 'visa', 'my', 100, 0.0225),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::MYR, 200, 'master', 'my', 100, 0.0225),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::MYR, 200, 'mastercard', 'my', 100, 0.0225),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::MYR, 200, 'amex', 'my', 100, 0.039),

            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'visa', 'my', 100, 0.039),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'master', 'my', 100, 0.039),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'mastercard', 'my', 100, 0.039),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'amex', 'my', 100, 0.039),

            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::MYR, 200, 'visa', 'us', 100, 0.039),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::MYR, 200, 'master', 'us', 100, 0.039),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::MYR, 200, 'mastercard', 'us', 100, 0.039),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::MYR, 200, 'amex', 'us', 100, 0.039),

            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'visa', 'us', 100, 0.039),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'master', 'us', 100, 0.039),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'mastercard', 'us', 100, 0.039),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'amex', 'us', 100, 0.039),

            new ChargeConfig(PaymentMethodType::ALIPAY, CurrencyCode::MYR, 200, null, null, 100, 0.03),
            new ChargeConfig(PaymentMethodType::GRABPAY, CurrencyCode::MYR, 200, null, null, 0, 0.033),
            new ChargeConfig(PaymentMethodType::FPX, CurrencyCode::MYR, 200, null, null, 100, 0.03),
        ]);
    }

    public function testAustraliaStripePaymentProvider()
    {
        $paymentProvider = Business\PaymentProvider::query()
            ->whereDoesntHave('rates')
            ->whereHas('business', function (\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->where('country', 'au');
            })
            ->where([
                'payment_provider' => 'stripe_us',
            ])
            ->latest()
            ->first();

        $this->doTestChargeFee($paymentProvider, [
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::AUD, 100, 'visa', 'au', 50, 0.015),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::AUD, 100, 'master', 'au', 50, 0.015),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::AUD, 100, 'mastercard', 'au', 50, 0.015),
            // new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::AUD, 100, 'amex', 'au', 50, 0.03),

            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'visa', 'au', 50, 0.03),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'master', 'au', 50, 0.03),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'mastercard', 'au', 50, 0.03),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'amex', 'au', 50, 0.03),

            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::AUD, 100, 'visa', 'us', 50, 0.03),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::AUD, 100, 'master', 'us', 50, 0.03),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::AUD, 100, 'mastercard', 'us', 50, 0.03),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::AUD, 100, 'amex', 'us', 50, 0.03),

            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'visa', 'us', 50, 0.03),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'master', 'us', 50, 0.03),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'mastercard', 'us', 50, 0.03),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'amex', 'us', 50, 0.03),

            new ChargeConfig(PaymentMethodType::ALIPAY, CurrencyCode::AUD, 100, null, null, 50, 0.035),
        ]);
    }

    public function testNewZealandStripePaymentProvider()
    {
        $paymentProvider = Business\PaymentProvider::query()
            ->whereDoesntHave('rates')
            ->whereHas('business', function (\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->where('country', 'nz');
            })
            ->where([
                'payment_provider' => 'stripe_us',
            ])
            ->latest()
            ->first();

        $this->doTestChargeFee($paymentProvider, [
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::NZD, 100, 'visa', 'nz', 50, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::NZD, 100, 'master', 'nz', 50, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::NZD, 100, 'mastercard', 'nz', 50, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::NZD, 100, 'amex', 'nz', 50, 0.024),

            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'visa', 'nz', 50, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'master', 'nz', 50, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'mastercard', 'nz', 50, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'amex', 'nz', 50, 0.024),

            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::NZD, 100, 'visa', 'us', 50, 0.03),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::NZD, 100, 'master', 'us', 50, 0.03),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::NZD, 100, 'mastercard', 'us', 50, 0.03),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::NZD, 100, 'amex', 'us', 50, 0.03),

            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'visa', 'us', 50, 0.03),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'master', 'us', 50, 0.03),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'mastercard', 'us', 50, 0.03),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'amex', 'us', 50, 0.03),

            new ChargeConfig(PaymentMethodType::ALIPAY, CurrencyCode::NZD, 100, null, null, 50, 0.035),
        ]);
    }

    public function testUnitedStateStripePaymentProvider()
    {
        $paymentProvider = Business\PaymentProvider::query()
            ->whereDoesntHave('rates')
            ->whereHas('business', function (\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->where('country', 'us');
            })
            ->where([
                'payment_provider' => 'stripe_us',
            ])
            ->latest()
            ->first();

        $this->doTestChargeFee($paymentProvider, [
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'visa', 'us', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'master', 'us', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'mastercard', 'us', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'amex', 'us', 40, 0.024),

            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::SGD, 100, 'visa', 'us', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::SGD, 100, 'master', 'us', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::SGD, 100, 'mastercard', 'us', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::SGD, 100, 'amex', 'us', 40, 0.024),

            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'visa', 'sg', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'master', 'sg', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'mastercard', 'sg', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'amex', 'sg', 40, 0.024),

            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::SGD, 100, 'visa', 'sg', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::SGD, 100, 'master', 'sg', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::SGD, 100, 'mastercard', 'sg', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::SGD, 100, 'amex', 'sg', 40, 0.024),

            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::USD, 100, 'visa', 'us', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::USD, 100, 'master', 'us', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::USD, 100, 'mastercard', 'us', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::USD, 100, 'amex', 'us', 40, 0.024),

            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::SGD, 100, 'visa', 'us', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::SGD, 100, 'master', 'us', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::SGD, 100, 'mastercard', 'us', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::SGD, 100, 'amex', 'us', 40, 0.024),

            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::USD, 100, 'visa', 'sg', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::USD, 100, 'master', 'sg', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::USD, 100, 'mastercard', 'sg', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::USD, 100, 'amex', 'sg', 40, 0.024),

            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::SGD, 100, 'visa', 'sg', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::SGD, 100, 'master', 'sg', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::SGD, 100, 'mastercard', 'sg', 40, 0.024),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::SGD, 100, 'amex', 'sg', 40, 0.024),

            new ChargeConfig(PaymentMethodType::ALIPAY, CurrencyCode::USD, 100, null, null, 40, 0.035),
        ]);
    }

    public function testSingaporeStripePaymentProvider()
    {
        $paymentProvider = Business\PaymentProvider::query()->whereDoesntHave('rates')->where([
            'payment_provider' => 'stripe_sg',
        ])->latest()->first();

        $this->doTestChargeFee($paymentProvider, [
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::SGD, 100, 'visa', 'sg', 50, 0.028),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::SGD, 100, 'master', 'sg', 50, 0.028),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::SGD, 100, 'mastercard', 'sg', 50, 0.028),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::SGD, 100, 'amex', 'sg', 50, 0.0365),

            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'visa', 'sg', 50, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'master', 'sg', 50, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'mastercard', 'sg', 50, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'amex', 'sg', 50, 0.0365),

            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::SGD, 100, 'visa', 'us', 50, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::SGD, 100, 'master', 'us', 50, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::SGD, 100, 'mastercard', 'us', 50, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::SGD, 100, 'amex', 'us', 50, 0.0365),

            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'visa', 'us', 50, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'master', 'us', 50, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'mastercard', 'us', 50, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD, CurrencyCode::USD, 100, 'amex', 'us', 50, 0.0365),

            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::SGD, 100, 'visa', 'sg', 60, 0.025),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::SGD, 100, 'master', 'sg', 60, 0.025),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::SGD, 100, 'mastercard', 'sg', 60, 0.025),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::SGD, 100, 'amex', 'sg', 60, 0.0365),

            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::USD, 100, 'visa', 'sg', 60, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::USD, 100, 'master', 'sg', 60, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::USD, 100, 'mastercard', 'sg', 60, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::USD, 100, 'amex', 'sg', 60, 0.0365),

            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::SGD, 100, 'visa', 'us', 60, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::SGD, 100, 'master', 'us', 60, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::SGD, 100, 'mastercard', 'us', 60, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::SGD, 100, 'amex', 'us', 60, 0.0365),

            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::USD, 100, 'visa', 'us', 60, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::USD, 100, 'master', 'us', 60, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::USD, 100, 'mastercard', 'us', 60, 0.0365),
            new ChargeConfig(PaymentMethodType::CARD_PRESENT, CurrencyCode::USD, 100, 'amex', 'us', 60, 0.0365),

            new ChargeConfig(PaymentMethodType::ALIPAY, CurrencyCode::SGD, 100, null, null, 35, 0.027),
            new ChargeConfig(PaymentMethodType::ALIPAY, CurrencyCode::USD, 100, null, null, 50, 0.0365),
        ]);
    }

    public function testSingaporeDbsPaymentProvider()
    {
        $paymentProvider = Business\PaymentProvider::query()->whereDoesntHave('rates')->where([
            'payment_provider' => 'dbs_sg',
        ])->latest()->first();

        // Not doing test for other currencies because both of these currently supports SGD only.
        //
        $this->doTestChargeFee($paymentProvider, [
            new ChargeConfig(PaymentMethodType::PAYNOW, CurrencyCode::SGD, 100, null, null, 20, 0),
            new ChargeConfig(PaymentMethodType::PAYNOW, CurrencyCode::SGD, 5000, null, null, 0, 0.009),
            new ChargeConfig(PaymentMethodType::PAYNOW, CurrencyCode::SGD, 10000, null, null, 30, 0.0065),
            new ChargeConfig(PaymentMethodType::DIRECT_DEBIT, CurrencyCode::SGD, 100, null, null, 225, 0.0065),
            new ChargeConfig(PaymentMethodType::DIRECT_DEBIT, CurrencyCode::SGD, 5000, null, null, 225, 0.0065),
            new ChargeConfig(PaymentMethodType::DIRECT_DEBIT, CurrencyCode::SGD, 10000, null, null, 225, 0.0065),
        ]);
    }

    public function testSingaporeGrabpayPaymentProvider()
    {
        $paymentProvider = Business\PaymentProvider::query()->whereDoesntHave('rates')->where([
            'payment_provider' => 'shopee_pay',
        ])->latest()->first();

        // Not doing test for other currencies because both of these currently supports SGD only.
        //
        $this->doTestChargeFee($paymentProvider, [
            new ChargeConfig(PaymentMethodType::GRABPAY_DIRECT, CurrencyCode::SGD, 100, null, null, 0, 0.03),
            new ChargeConfig(PaymentMethodType::GRABPAY_PAYLATER, CurrencyCode::SGD, 100, null, null, 0, 0.055),
        ]);
    }

    public function _testSingaporeZipPaymentProvider()
    {
        $paymentProvider = Business\PaymentProvider::query()->whereDoesntHave('rates')->where([
            'payment_provider' => 'zip',
        ])->latest()->first();

        // Not doing test for other currencies because Zip currently supports SGD only.
        //
        $this->doTestChargeFee($paymentProvider, [
            new ChargeConfig(PaymentMethodType::ZIP, CurrencyCode::SGD, 100, null, null, 20, 0),
        ]);
    }

    public function testSingaporeShopeePayPaymentProvider()
    {
        $paymentProvider = Business\PaymentProvider::query()->whereDoesntHave('rates')->where([
            'payment_provider' => 'grabpay',
        ])->latest()->first();

        // Not doing test for other currencies because Shopee currently supports SGD only.
        //
        $this->doTestChargeFee($paymentProvider, [
            new ChargeConfig(PaymentMethodType::SHOPEE, CurrencyCode::SGD, 100, null, null, 0, 0.03),
        ]);
    }

    private function doTestChargeFee(Business\PaymentProvider $paymentProvider, array $cases) : void
    {
        /**
         * @var \HitPay\Test\Objects\ChargeConfig $case
         */
        foreach ($cases as $case) {
            // string $homeCountry, string $homeCurrency, string $chargeCurrency, string $channel = null, string $method = null,
            // string $cardCountry = null, string $cardBrand = null, int $amount = null
            [
                $fixedFee,
                $discountRate,
            ] = $paymentProvider->getRateFor(
                $case->getCurrency(),
                'point_of_sale',
                $case->getMethod(),
                $case->getCardCountry(),
                $case->getCardBrand(),
                $case->getAmount()
            );

            $this->assertEquals($fixedFee, $case->getExpectedFixedFee());
            $this->assertEquals($discountRate, $case->getExpectedDiscountRate());
        }
    }
}
