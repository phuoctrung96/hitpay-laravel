<?php

namespace App\Actions\Business\Stripe\Charge;

use App\Actions\Exceptions;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\PaymentProvider;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @mixin \App\Actions\Business\Stripe\Charge\Action
 */
trait BusinessPaymentIntentValidator
{
    /**
     * Validate the business payment intent.
     *
     * @param  string  ...$methods
     *
     * @return void
     * @throws \Exception
     */
    protected function validateBusinessPaymentIntent(string ...$methods) : void
    {
        $businessPaymentProvider = $this->getBusinessPaymentProvider();

        if ($this->businessPaymentIntent->payment_provider !== $businessPaymentProvider->payment_provider) {
            throw new Exception("The payment intent (ID : {$this->businessPaymentIntentId}) is having different payment provider with payment provider (ID : {$businessPaymentProvider->getKey()})");
        }

        $paymentProviderMap = [
            PaymentProvider::STRIPE_MALAYSIA => 'stripe',
            PaymentProvider::STRIPE_SINGAPORE => 'stripe',
        ];

        $paymentProvider = $paymentProviderMap[$businessPaymentProvider->payment_provider] ?? false;

        if ($paymentProvider === false) {
            throw new Exception(
                "The payment provider (Code : {$businessPaymentProvider->payment_provider}) is unavailable."
            );
        }

        if ($this->businessPaymentIntent->expires_at instanceof Carbon
            && $this->businessPaymentIntent->expires_at->isPast()) {
            throw new Exceptions\BadRequest("The payment intent (ID : {$this->businessPaymentIntentId}) is expired.");
        }

        $paymentIntentObjectTypeMap = [
            'stripe' => [
                PaymentMethodType::ALIPAY => Action::SOURCE,
                PaymentMethodType::CARD => Action::PAYMENT_INTENT,
                PaymentMethodType::CARD_PRESENT => Action::PAYMENT_INTENT,
                PaymentMethodType::GRABPAY => Action::PAYMENT_INTENT,
                PaymentMethodType::WECHAT => Action::SOURCE,
                PaymentMethodType::FPX => Action::PAYMENT_INTENT,                
            ],
        ];

        $paymentIntentObjectTypeMap = $paymentIntentObjectTypeMap[$paymentProvider];

        foreach ($methods as $method) {
            $expectedPaymentIntentObjectType = $paymentIntentObjectTypeMap[$method] ?? false;

            switch (true) {
                case $expectedPaymentIntentObjectType === false:
                case $this->businessPaymentIntent->payment_provider_method !== $method:
                case $this->businessPaymentIntent->payment_provider_object_type !== $expectedPaymentIntentObjectType:
                    continue 2;
            }

            return;
        }

        $methods = Collection::make($methods)->join('", "', '" or "');

        throw new Exception("The payment intent (ID : {$this->businessPaymentIntentId}, Payment Provider Method : {$this->businessPaymentIntent->payment_provider_method}, Payment Provider Object Type : {$this->businessPaymentIntent->payment_provider_object_type}) is invalid, expected method \"{$methods}\".");
    }
}
