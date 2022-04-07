<?php

namespace App\Actions\Business\Stripe\Charge\Source;

use App\Actions\Business\Stripe\Charge\Action;
use App\Actions\Business\Stripe\Charge\PaymentMethodValidator;
use App\Actions\Exceptions\BadRequest;
use App\Business;
use App\Enumerations\Business\PaymentMethodType;
use App\Logics\ConfigurationRepository;
use Exception;
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
            PaymentMethodType::ALIPAY,
            PaymentMethodType::WECHAT,
        ]);

        $sourceParametersMetadata = [];

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

        $sourceParameters = [
            'type' => $method,
            'currency' => $this->businessCharge->currency,
            'amount' => $this->businessCharge->amount,
            'statement_descriptor' => $this->business->statementDescription($statementDescriptor ?? null),
            'metadata' => $sourceParametersMetadata,
        ];

        if ($method === PaymentMethodType::ALIPAY) {
            if (isset($this->data['return_url'])) {
                if (!Facades\URL::isValidUrl($this->data['return_url'])) {
                    throw new Exception('The given redirect URL is invalid.');
                }

                $sourceParameters['redirect']['return_url'] = $this->data['return_url'];
            } else {
                $sourceParameters['redirect']['return_url'] = Facades\URL::route('close');
            }
        }

        $stripeSource = Stripe\Source::create($sourceParameters);

        $businessPaymentIntent = new Business\PaymentIntent;

        $businessPaymentIntent->business_id = $this->businessCharge->business_id;
        $businessPaymentIntent->payment_provider = $businessPaymentProvider->payment_provider;
        $businessPaymentIntent->payment_provider_account_id = $businessPaymentProvider->payment_provider_account_id;
        $businessPaymentIntent->payment_provider_method = $method;
        $businessPaymentIntent->currency = $this->businessCharge->currency;
        $businessPaymentIntent->amount = $this->businessCharge->amount;
        $businessPaymentIntent->payment_provider_object_type = $stripeSource->object;
        $businessPaymentIntent->payment_provider_object_id = $stripeSource->id;
        $businessPaymentIntent->status = $stripeSource->status;
        $businessPaymentIntent->data = [
            'stripe' => [
                'source' => $stripeSource->toArray(),
            ],
        ];

        $businessPaymentIntent->expires_at = Facades\Date::createFromTimestamp($stripeSource->created)->addHours(3);

        Facades\DB::transaction(function () use ($businessPaymentIntent) {
            $this->businessCharge->paymentIntents()->save($businessPaymentIntent);
        }, 3);

        return $businessPaymentIntent;
    }
}
