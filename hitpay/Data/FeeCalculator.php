<?php

namespace HitPay\Data;

use App\Business;
use Exception;
use HitPay\Data\Countries\Objects\PaymentProvider;
use HitPay\Data\Objects\Fee;
use HitPay\Data\Objects\PaymentMethods\Card;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @method static $this forBusinessPaymentIntent( Business\PaymentIntent $businessPaymentIntent )
 */
class FeeCalculator
{
    protected string $initializedWith;

    protected Business $business;

    protected ?Business $platform;

    protected Business\PaymentProvider $selectedPaymentProvider;

    protected string $selectedPaymentProviderOf = 'business';

    protected PaymentProvider $paymentProviderConfiguration;

    protected string $paymentProviderMethod;

    protected ?string $cardCountry = null;

    protected ?string $cardBrand = null;

    protected string $channel;

    protected string $currency;

    protected int $amount;

    protected Collection $fillable;

    /**
     * Initiate with a business payment intent to calculate the fee.
     *
     * @param  \App\Business\PaymentIntent  $businessPaymentIntent
     *
     * @return $this
     */
    protected function withBusinessPaymentIntent(Business\PaymentIntent $businessPaymentIntent) : self
    {
        $this->initializedWith = get_class($businessPaymentIntent);

        $this->business = $businessPaymentIntent->business;

        $businessCharge = $businessPaymentIntent->charge;

        // TODO - KIV By Bankorh
        //   --------------------->>>
        //   Check the logic here, the logic here is, if the platform has payment provider enabled for this intent,
        //   we use the rate from that payment provider, else we use the one from business. However, from my
        //   understanding, the platform and the business must have the same payment provider enabled to do charge.
        //
        if ($businessCharge->platform_business_id) {
            $this->platform = Business::find($businessCharge->platform_business_id);

            if ($this->platform instanceof Business && $this->platform->country === $this->business->country) {
                $selectedPaymentProvider = $this->platform->paymentProviders()
                    ->where('payment_provider', $businessPaymentIntent->payment_provider)
                    ->first();
            }
        }

        if (isset($selectedPaymentProvider) && $selectedPaymentProvider instanceof Business\PaymentProvider) {
            $this->selectedPaymentProvider = $selectedPaymentProvider;
            $this->selectedPaymentProviderOf = 'platform';
        } else {
            $this->selectedPaymentProvider = $this->business->paymentProviders()
                ->where('payment_provider', $businessPaymentIntent->payment_provider)
                ->first();
        }

        $this->paymentProviderConfiguration = Countries::get($this->business->country)->paymentProviders()
            ->where('code', $this->selectedPaymentProvider->payment_provider)
            ->first();

        $this->paymentProviderMethod = $businessPaymentIntent->payment_provider_method;
        $this->channel = $businessCharge->channel;
        $this->currency = $businessPaymentIntent->currency;
        $this->amount = $businessPaymentIntent->amount;

        if ($this->paymentProviderConfiguration->official_code === 'stripe') {
            $card = $businessPaymentIntent->card();

            if ($card instanceof Card) {
                $this->cardBrand = $card->brand;
                $this->cardCountry = $card->country;
            }
        }

        return $this;
    }

    /**
     * Just calculate.
     *
     * @return \HitPay\Data\Objects\Fee
     * @throws \ReflectionException
     */
    public function calculate() : Fee
    {
        [
            $homeCurrencyFixedFeeAmount,
            $discountFeeRate,
        ] = $this->selectedPaymentProvider->getRateFor(
            $this->business->country,
            $this->business->currency,
            $this->currency,
            $this->channel,
            $this->paymentProviderMethod,
            $this->cardCountry,
            $this->cardBrand,
            $this->amount
        );

        $exchangeRate = ExchangeRate::new($this->business->currency, $this->currency)->get();

        $settlementCurrencyDiscountFeeAmount = (int) bcmul($this->amount, $discountFeeRate);

        $homeCurrencyBreakdown = new Fee\Breakdown(
            $homeCurrencyFixedFeeAmount,
            (int) bcmul($settlementCurrencyDiscountFeeAmount, $exchangeRate),
        );

        $settlementCurrencyBreakdown = new Fee\Breakdown(
            (int) bcdiv($homeCurrencyFixedFeeAmount, $exchangeRate),
            $settlementCurrencyDiscountFeeAmount,
        );

        return new Fee(
            $this->business->currency,
            $this->currency,
            $exchangeRate,
            $discountFeeRate,
            $homeCurrencyBreakdown,
            $settlementCurrencyBreakdown,
            $this->selectedPaymentProviderOf === 'platform',
        );
    }

    /**
     * Resolve a new instance.
     */
    public static function __callStatic(string $name, $arguments)
    {
        if (!Str::startsWith($name, 'for')) {
            throw new Exception;
        }

        $name = Str::replaceFirst('for', 'with', $name);

        $static = new static;

        if (!method_exists($static, $name)) {
            throw new Exception;
        }

        return $static->{$name}(...$arguments);
    }
}
