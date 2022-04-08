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
        ]);

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
        $sourceParametersMetadata['environment'] = Facades\Config::get('env');

        if (key_exists('remark', $this->data) && is_string($this->data['remark'])) {
            $statementDescriptor = $this->data['remark'];
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

        $stripePaymentIntent = Stripe\PaymentIntent::create($stripePaymentIntentParameters);

        $businessPaymentIntent->payment_provider_object_type = $stripePaymentIntent->object;
        $businessPaymentIntent->payment_provider_object_id = $stripePaymentIntent->id;
        $businessPaymentIntent->status = $stripePaymentIntent->status;

        $businessPaymentIntentData['stripe']['payment_intent'] = $stripePaymentIntent->toArray();

        $businessPaymentIntent->data = $businessPaymentIntentData;

        Facades\DB::transaction(function () use ($businessPaymentIntent) {
            $this->businessCharge->paymentIntents()->save($businessPaymentIntent);
        }, 3);

        if ($method === 'grabpay') {
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
