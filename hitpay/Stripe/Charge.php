<?php

namespace HitPay\Stripe;

use App\Business\Charge as ChargeModel;
use App\Logics\ConfigurationRepository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Stripe\Charge as StripeCharge;
use Stripe\PaymentIntent;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\Source as StripeSource;
use Stripe\Transfer as StripeTransfer;

/**
 * @deprecated
 */
class Charge extends Core
{
    /**
     * Create source.
     *
     * @param  string  $type
     * @param  string  $currency
     * @param  int  $amount
     * @param  string|null  $statementDescriptor
     * @param  array|null  $parameters
     * @param  array  $expanding
     *
     * @return \Stripe\Source
     * @throws \Stripe\Exception\ApiErrorException
     * @deprecated
     */
    public function createSource(
        string $type, string $currency, int $amount, string $statementDescriptor = null, array $parameters = null,
        array $expanding = []
    ) : StripeSource {

        $appName = Config::get('app.name');

        return StripeSource::create($expanding + [
                'type' => $type,
                'currency' => $currency,
                'amount' => $amount,
                'metadata' => ($parameters['metadata'] ?? []) + [
                        'platform' => $appName,
                        'version' => ConfigurationRepository::get('platform_version'),
                        'environment' => Config::get('app.env'),
                    ],
                'statement_descriptor' => Str::limit(preg_replace("/[^a-zA-Z0-9]+/", '', $statementDescriptor ?? $appName), 22, ''),
            ]);
    }

    /**
     * Update the metadata of a source.
     *
     * @param string $sourceId
     * @param array $metadata
     *
     * @return \Stripe\Source
     * @throws \Stripe\Exception\ApiErrorException
     * @deprecated
     */
    public function updateSource(string $sourceId, array $metadata)
    {
        return StripeSource::update($sourceId, [
            'metadata' => $metadata,
        ]);
    }

    /**
     * @param string $sourceId
     *
     * @return \Stripe\Source
     * @throws \Stripe\Exception\ApiErrorException
     * @deprecated
     */
    public function retrieveSource(string $sourceId)
    {
        return StripeSource::retrieve($sourceId);
    }

    /**
     * @param string $chargeId
     * @param string $accountId
     * @param string $currency
     * @param int $amount
     * @param \App\Business\Charge $charge
     *
     * @return \Stripe\Transfer
     * @throws \Stripe\Exception\ApiErrorException
     * @deprecated
     */
    public function transfer(string $chargeId, string $accountId, string $currency, int $amount, ChargeModel $charge)
    {
        return StripeTransfer::create([
            'amount' => $amount,
            'currency' => $currency,
            'destination' => $accountId,
            'source_transaction' => $chargeId,
            'metadata' => [
                'platform' => Config::get('app.name'),
                'version' => ConfigurationRepository::get('platform_version'),
                'environment' => Config::get('app.env'),
                'business_id' => $charge->business_id,
                'charge_id' => $charge->getKey(),
            ],
        ]);
    }

    /**
     * @param string $sourceId
     * @param string $currency
     * @param int $amount
     * @param array $metadata
     * @param string|null $remark
     *
     * @return \Stripe\Charge
     * @throws \Stripe\Exception\ApiErrorException
     * @deprecated
     */

    public function chargeSource(
        string $sourceId, string $businessId, string $businessChargeId, string $currency, int $amount,
        string $statementDescriptor = null, string $remark = null, bool $forceNoStatementDescriptor = false
    ) {
        $data = [
            'amount' => $amount,
            'currency' => $currency,
            'description' => $remark,
            'metadata' => [
                'platform' => Config::get('app.name'),
                'version' => ConfigurationRepository::get('platform_version'),
                'environment' => Config::get('app.env'),
                'business_id' => $businessId,
                'charge_id' => $businessChargeId,
            ],
            'source' => $sourceId,
        ];

        if (!is_null($statementDescriptor) && $forceNoStatementDescriptor === false) {
            $data['statement_descriptor'] = Str::limit(preg_replace("/[^a-zA-Z0-9]+/", '', $statementDescriptor), 22, '');
        }

        return StripeCharge::create($data);
    }

    /**
     * @param string $chargeId
     *
     * @return \Stripe\Charge
     * @throws \Stripe\Exception\ApiErrorException
     * @deprecated
     */
    public function retrieveCharge(string $chargeId)
    {
        return StripeCharge::retrieve($chargeId);
    }

    /**
     * Create a payment intent.
     *
     * @param string $onBehalfOf
     * @param string $currency
     * @param int $amount
     * @param string $statementDescriptor
     * @param array|null $parameters
     *
     * @return \Stripe\PaymentIntent
     * @throws \Stripe\Exception\ApiErrorException
     * @deprecated
     */
    public function createPaymentIntent(
        string $onBehalfOf, string $currency, int $amount, string $statementDescriptor = null,
        array $parameters = null
    ) : StripePaymentIntent {

        $appName = Config::get('app.name');

        return StripePaymentIntent::create([
            'currency' => $currency,
            'amount' => $amount,
            'description' => $parameters['remark'] ?? null,
            'metadata' => ($parameters['metadata'] ?? []) + [
                    'platform' => $appName,
                    'version' => ConfigurationRepository::get('platform_version'),
                    'environment' => Config::get('app.env'),
                ],
            'statement_descriptor' => Str::limit(preg_replace("/[^a-zA-Z0-9]+/", '', $statementDescriptor ?? $appName), 22, ''),
            'on_behalf_of' => $onBehalfOf,
            'capture_method' => $parameters['capture_method'] ?? 'automatic',
            'payment_method_types' => $parameters['payment_method_types'] ?? [
                    'card',
                    'card_present',
                ],
        ]);
    }

    /**
     * Update the metadata of a payment intent.
     *
     * @param string $paymentIntentId
     * @param array $metadata
     *
     * @return \Stripe\PaymentIntent
     * @throws \Stripe\Exception\ApiErrorException
     * @deprecated
     */
    public function updatePaymentIntent(string $paymentIntentId, array $metadata)
    {
        return PaymentIntent::update($paymentIntentId, [
            'metadata' => $metadata,
        ]);
    }

    /**
     * Retrieve a payment intent.
     *
     * @param string $paymentIntentId
     *
     * @return \Stripe\PaymentIntent
     * @throws \Stripe\Exception\ApiErrorException
     * @deprecated
     */
    public function retrievePaymentIntent(string $paymentIntentId)
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    /**
     * Capture a payment intent.
     *
     * @param string $paymentIntentId
     *
     * @return \Stripe\PaymentIntent
     * @throws \Stripe\Exception\ApiErrorException
     * @deprecated
     */
    public function capturePaymentIntent(string $paymentIntentId)
    {
        $paymentIntent = $this->retrievePaymentIntent($paymentIntentId);

        $paymentIntent->capture();

        return $this->retrievePaymentIntent($paymentIntentId);
    }
}
