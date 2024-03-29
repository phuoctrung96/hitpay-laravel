<?php

namespace App\Actions\Business\Stripe\Charge\PaymentIntent;

use App\Actions\Business\Stripe\Charge\Action;
use App\Actions\Business\Stripe\Charge\PaymentMethodValidator;
use App\Actions\Exceptions\BadRequest;
use App\Business;
use App\Logics\ConfigurationRepository;
use HitPay\Data\FeeCalculator;
use Illuminate\Support\Facades;
use Stripe;

class Create extends Action
{
    use PaymentMethodValidator;

    /**
     * @return \App\Business\PaymentIntent
     * @throws \App\Actions\Exceptions\BadRequest
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function process() : Business\PaymentIntent
    {
        if (!$this->businessCharge->requiresPaymentMethod()) {
            throw new BadRequest("The status of the charge doesn't allow to accept payment now.");
        }

        $businessPaymentProvider = $this->getBusinessPaymentProvider();

        // TODO - 20220120
        //   --------------->>>
        //   -
        //   We should get the capabilities which intersects in both our platform and the connected accounts, not
        //   hardcode them here.
        //

        $method = $this->validatePaymentMethodRequest($this->getStripeConfigurations(), [
            'card',
            'card_present',
            'grabpay',
            'fpx',
        ]);

        if (in_array($method, [ 'card', 'card_present' ]) && !$this->business->payment_enabled) {
            throw new BadRequest(
                "This account can't accept any card payments until it is approved by us. Please contact our support for more details."
            );
        }

        $businessPaymentIntent = new Business\PaymentIntent;

        $businessPaymentIntent->business_id = $this->businessCharge->business_id;
        $businessPaymentIntent->payment_provider = $businessPaymentProvider->payment_provider;
        $businessPaymentIntent->payment_provider_account_id = $businessPaymentProvider->payment_provider_account_id;
        $businessPaymentIntent->payment_provider_method = $method;
        $businessPaymentIntent->currency = $this->businessCharge->currency;
        $businessPaymentIntent->amount = $this->businessCharge->amount;

        if (key_exists('metadata', $this->data) && is_array($this->data['metadata'])) {
            foreach ($this->data['metadata'] as $key => $value) {
                if (is_string($value) || is_numeric($value) || is_bool($value)) {
                    $sourceParametersMetadata[$key] = $value;
                }
            }
        }

        // We set the following metadata after populating the extra metadata above because we want the following
        // metadata to be present.
        //
        $sourceParametersMetadata['charge_id'] = $this->businessChargeId;
        $sourceParametersMetadata['platform'] = Facades\Config::get('app.name');
        $sourceParametersMetadata['version'] = ConfigurationRepository::get('platform_version');
        $sourceParametersMetadata['environment'] = Facades\Config::get('app.env');

        if (key_exists('remark', $this->data) && is_string($this->data['remark'])) {
            $statementDescriptor = $this->data['remark'];
        }

        if (
            $businessPaymentIntent->payment_provider_method === 'card_present'
            && key_exists('terminal_id', $this->data)
            && is_string($this->data['terminal_id'])
            && !blank($this->data['terminal_id'])
        ) {
            $hitpayPaymentIntentParameters = [
                'terminal' => [
                    'serial_number' => $this->data['terminal_id']
                ],
            ];
        }

        $stripePaymentIntentParameters = [
            'currency' => $this->businessCharge->currency,
            'amount' => $this->businessCharge->amount,
            'metadata' => $sourceParametersMetadata,
            'statement_descriptor' => $this->business->statementDescription($statementDescriptor ?? null),
            'on_behalf_of' => $businessPaymentProvider->payment_provider_account_id,
            'transfer_data' => [
                'destination' => $businessPaymentProvider->payment_provider_account_id,
            ],
            'application_fee_amount' => null,
            'payment_method_types' => [ $method ],
            'confirm' => false,
            'use_stripe_sdk' => true,
        ];

        $businessPaymentIntentData = [];

        if ($method === 'card') {
            $stripePaymentIntentParameters['confirmation_method'] = 'manual';
        } elseif ($method === 'card_present') {
            $stripePaymentIntentParameters['capture_method'] = 'manual';
            $stripePaymentIntentParameters['confirmation_method'] = 'manual';
        }

        try {
            $stripePaymentIntent = Stripe\PaymentIntent::create($stripePaymentIntentParameters);
        } catch (Stripe\Exception\ApiErrorException $exception) {
            $stripeAccount = Stripe\Account::retrieve($businessPaymentIntent->payment_provider_account_id);

            Facades\Log::info(
                "The business (ID : {$this->business->getKey()}, Charge Enabled: `{$stripeAccount->charges_enabled}`, Disabled Reason: `{$stripeAccount->requirements->disabled_reason}`) is having issue when a customer is intending to apy them via Stripe. Got code `{$exception->getStripeCode()}` and message: {$exception->getMessage()}"
            );

            throw new BadRequest(
                'Failed to complete the payment, please contact the merchant or try another payment method.'
            );
        }

        $businessPaymentIntent->payment_provider_object_type = $stripePaymentIntent->object;
        $businessPaymentIntent->payment_provider_object_id = $stripePaymentIntent->id;
        $businessPaymentIntent->status = $stripePaymentIntent->status;

        $businessPaymentIntentData['stripe']['payment_intent'] = $stripePaymentIntent->toArray();

        if (isset($hitpayPaymentIntentParameters)) {
            $businessPaymentIntentData['hitpay'] = $hitpayPaymentIntentParameters;
        }

        $businessPaymentIntent->data = $businessPaymentIntentData;

        Facades\DB::transaction(function () use ($businessPaymentIntent) {
            $this->businessCharge->paymentIntents()->save($businessPaymentIntent);
        }, 3);

        if ($method === 'grabpay' || $method === 'fpx') {
            $applicationFee = FeeCalculator::forBusinessPaymentIntent($businessPaymentIntent)->calculate();

            $stripePaymentIntent = Stripe\PaymentIntent::update($stripePaymentIntent->id, [
                'application_fee_amount' => $applicationFee->settlement_currency_total_amount,
            ]);

            $businessPaymentIntentData = $businessPaymentIntent->data;

            $businessPaymentIntentData['application_fee'] = $applicationFee->toArray();
            $businessPaymentIntentData['stripe']['payment_intent'] = $stripePaymentIntent->toArray();

            $businessPaymentIntent->data = $businessPaymentIntentData;
        }

        Facades\DB::transaction(function () use ($businessPaymentIntent) {
            $businessPaymentIntent->save();
        }, 3);

        return $businessPaymentIntent;
    }
}
