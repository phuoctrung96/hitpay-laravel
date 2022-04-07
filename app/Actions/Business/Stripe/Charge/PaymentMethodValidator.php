<?php

namespace App\Actions\Business\Stripe\Charge;

use App\Actions\Exceptions\BadRequest;
use Closure;
use HitPay\Data\Countries\Objects\PaymentProvider;
use HitPay\Data\Countries\Objects\PaymentProvider\Method;
use HitPay\Data\Countries\Objects\PaymentProvider\Method\Currency;
use Illuminate\Support\Facades;
use Illuminate\Support\Str;

/**
 * @mixin \App\Actions\Business\Stripe\Charge\Action
 */
trait PaymentMethodValidator
{
    /**
     * This is a simple method only validator, a business charge must be set before this function is called.
     *
     *  TODO - 20220120
     *   --------------->>>
     *   -
     *   We should get the intersected capabilities of both our platform and the connected accounts, not just harcode
     *   them here.
     *
     * @param  \HitPay\Data\Countries\Objects\PaymentProvider  $stripeConfigurations
     * @param  array  $methodsEligible
     * @param  string  $attribute
     *
     * @return string
     * @throws \App\Actions\Exceptions\BadRequest
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validatePaymentMethodRequest(
        PaymentProvider $stripeConfigurations, array $methodsEligible, string $attribute = 'method'
    ) : string {
        $platformAvailableMethods = $stripeConfigurations->methods->whereIn('code', $methodsEligible);

        $selectedMethod = null;

        Facades\Validator::validate($this->data, [
            $attribute => [
                'required',
                'string',
                function (string $attribute, string $value, Closure $fail) use (
                    $platformAvailableMethods, &$selectedMethod
                ) {
                    $selectedMethod = $platformAvailableMethods->where('code', $value)->first();

                    if (!( $selectedMethod instanceof Method )) {
                        $_attributeLangeKey = "validation.attributes.{$attribute}";

                        if (Facades\Lang::has($_attributeLangeKey)) {
                            $attribute = Facades\Lang::get($_attributeLangeKey);
                        } else {
                            $attribute = Str::snake($attribute);
                            $attribute = preg_replace('/[^a-zA-Z0-9]+/', ' ', $attribute);
                        }

                        $fail(Facades\Lang::get('validation.in', compact('attribute')));
                    }
                },
            ],
        ]);

        $method = $selectedMethod->code;

        $selectedCurrency = $selectedMethod->currencies->where('code', $this->businessCharge->currency)->first();

        if (!( $selectedCurrency instanceof Currency )) {
            throw new BadRequest("The currency '{$this->businessCharge->currency}' for charge (ID : {$this->businessChargeId}) is invalid for the payment provider (Code : {$stripeConfigurations->code}) or invalid for the method (Code : {$method}) of the payment provider.");
        }

        return $method;
    }
}
