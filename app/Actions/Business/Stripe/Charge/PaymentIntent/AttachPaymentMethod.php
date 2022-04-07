<?php

namespace App\Actions\Business\Stripe\Charge\PaymentIntent;

use App\Actions\Business\Charges\InitiateRelatableUsingPaymentIntent;
use App\Actions\Business\Stripe\Charge\Action;
use App\Actions\Business\Stripe\Charge\BusinessPaymentIntentValidator;
use App\Actions\Exceptions\BadRequest;
use App\Actions\UseLogViaStorage;
use App\Business;
use Closure;
use HitPay\Data\FeeCalculator;
use Illuminate\Support\Facades;
use Stripe;
use Stripe\Exception\ApiErrorException;

class AttachPaymentMethod extends Action
{
    use BusinessPaymentIntentValidator;
    use InitiateRelatableUsingPaymentIntent;
    use UseLogViaStorage;

    public function businessPaymentIntent(Business\PaymentIntent $businessPaymentIntent) : Action
    {
        parent::businessPaymentIntent($businessPaymentIntent);

        $this->now = Facades\Date::now();

        $this->expectedBusinessChargeId = $businessPaymentIntent->business_charge_id;
        $this->paymentProviderName = $this->businessPaymentIntent->payment_provider;

        $this->setLogDirectories('payment_providers', $this->paymentProviderName, 'payment-intents');
        $this->setLogFilename(
            "{$this->businessPaymentIntent->business_charge_id}-{$this->businessPaymentIntent->payment_provider_object_id}.txt"
        );

        $this->initiateBusiness();
        $this->initiateBusinessCharge();

        $this->getBusinessPaymentProvider();

        return $this;
    }

    /**
     * Process.
     *
     * @return \App\Business\PaymentIntent
     * @throws \App\Actions\Exceptions\BadRequest
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function process() : Business\PaymentIntent
    {
        $this->validateBusinessPaymentIntent('card');

        $businessPaymentProvider = $this->getBusinessPaymentProvider();

        $stripePaymentIntent = Stripe\PaymentIntent::retrieve($this->businessPaymentIntent->payment_provider_object_id);

        if ($stripePaymentIntent->status !== 'requires_payment_method') {
            throw new BadRequest("Attaching a payment method to the payment intent (ID : {$this->businessPaymentIntent->id}) is failed because the status is not 'requires_source' but '{$stripePaymentIntent->status}'.");
        }

        $stripePaymentMethod = null;

        Facades\Validator::validate($this->data, [
            'payment_method' => [
                'required',
                'string',
                function (string $attribute, string $value, Closure $fail) use (&$stripePaymentMethod) {
                    try {
                        $stripePaymentMethod = Stripe\PaymentMethod::retrieve($value);

                        if ($stripePaymentMethod->type !== 'card') {
                            $fail($this->validationErrorMessage($attribute, 'in'));
                        }
                        //
                        // We can check is the card an amex here. If we want to filter the card.
                    } catch (ApiErrorException $exception) {
                        $fail($this->validationErrorMessage($attribute, 'in'));
                    }
                },
            ],
        ]);

        $businessPaymentIntentData = $this->businessPaymentIntent->data;

        $businessPaymentIntentData['stripe']['payment_intent'] = $stripePaymentIntent->toArray();
        $businessPaymentIntentData['stripe']['payment_method'] = $stripePaymentMethod->toArray();

        $this->businessPaymentIntent->data = $businessPaymentIntentData;

        $applicationFee = FeeCalculator::forBusinessPaymentIntent($this->businessPaymentIntent)->calculate();

        $stripePaymentIntentData['application_fee_amount'] = $applicationFee->settlement_currency_total_amount;
        $stripePaymentIntentData['payment_method'] = $stripePaymentMethod->id;

        $stripePaymentIntent = Stripe\PaymentIntent::update($stripePaymentIntent->id, $stripePaymentIntentData);

        $this->businessPaymentIntent->status = $stripePaymentIntent->status;

        $businessPaymentIntentData = $this->businessPaymentIntent->data;

        $businessPaymentIntentData['application_fee'] = $applicationFee->toArray();

        $this->businessPaymentIntent->data = $businessPaymentIntentData;

        $this->businessPaymentIntent->save();

        if ($stripePaymentIntent->status === 'requires_confirmation') {
            return Confirm::withBusinessPaymentIntent($this->businessPaymentIntent)->process();
        }

        return $this->businessPaymentIntent;
    }
}
