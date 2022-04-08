<?php

namespace App\Actions\Business\Stripe\Charge\Source;

use App\Actions\Business\Charges\InitiateRelatableUsingPaymentIntent;
use App\Actions\Business\Stripe\Charge\Action;
use App\Actions\Business\Stripe\Charge\BusinessPaymentIntentValidator;
use App\Actions\Exceptions;
use App\Actions\UseLogViaStorage;
use App\Business;
use App\Enumerations;
use App\Logics\ConfigurationRepository;
use Exception;
use HitPay\Data\FeeCalculator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades;
use Illuminate\Support\Str;
use Stripe;
use Throwable;

class ConfirmUsingChargeableSource extends Action
{
    use BusinessPaymentIntentValidator;
    use UseLogViaStorage;
    use InitiateRelatableUsingPaymentIntent;

    protected Stripe\Source $stripeSource;

    /**
     * Set the Stripe chargeable source.
     *
     * @param  \Stripe\Source  $stripeSource
     * @param  string  $paymentProviderName
     *
     * @return $this
     * @throws \App\Actions\Exceptions\BadRequest
     * @throws \App\Actions\Exceptions\UnexpectedError
     */
    public function chargeableSource(Stripe\Source $stripeSource, string $paymentProviderName)
    {
        $this->stripeSource = $stripeSource;
        $this->paymentProviderName = $paymentProviderName;

        $this->now = Facades\Date::now();

        $this->setExpectedBusinessChargeId();

        $this->setLogDirectories('payment_providers', $this->paymentProviderName, 'sources');
        $this->setLogFilename("{$this->expectedBusinessChargeId}-{$stripeSource->id}.txt");

        $this->log($this->stripeSource->toJSON());

        $this->initiateBusinessPaymentIntent();
        $this->initiateBusiness();
        $this->initiateBusinessCharge();

        $this->getBusinessPaymentProvider();

        return $this;
    }

    /**
     * Process.
     *
     * @return void
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Stripe\Exception\InvalidRequestException
     * @throws \Throwable
     */
    public function process()
    {
        $this->validateBusinessPaymentIntent('alipay', 'wechat');

        $applicationFee = FeeCalculator::forBusinessPaymentIntent($this->businessPaymentIntent)->calculate();

        try {
            $data = [
                'amount' => $this->businessPaymentIntent->amount,
                'currency' => $this->businessPaymentIntent->currency,
                'description' => $this->businessCharge->remark,
                'metadata' => [
                    'platform' => Facades\Config::get('app.name'),
                    'version' => ConfigurationRepository::get('platform_version'),
                    'environment' => Facades\Config::get('env'),
                    'business_id' => $this->businessId,
                    'charge_id' => $this->businessChargeId,
                ],
                'source' => $this->stripeSource->id,
                'transfer_data' => [
                    'destination' => $this->businessPaymentProvider->payment_provider_account_id,
                ],
                'application_fee_amount' => $applicationFee->settlement_currency_total_amount,
            ];

            $stripeCharge = Stripe\Charge::create($data);

            $this->log("{$stripeCharge->toJSON()}\n");
        } catch (Stripe\Exception\InvalidRequestException $exception) {
            $exceptionClassName = get_class($exception);
            $this->stripeSourceJson = json_encode($this->stripeSource->toArray());

            $this->log(<<<_MESSAGE
=============================
= ERROR  :  Charging Failed =
=============================

    Class        :  {$exceptionClassName}
    Location     :  {$exception->getFile()}:{$exception->getLine()}
    Message      :  {$exception->getMessage()}
    Http Status  :  {$exception->getHttpStatus()}
    Stripe Code  :  {$exception->getStripeCode()}
    Remarks      :  Failed to charge the chargeable source (ID : {$this->stripeSource->id}) for payment intent
                    (ID : {$this->businessPaymentIntent->getKey()}).

{$exception->getHttpBody()}

{$this->stripeSourceJson}

{$exception->getTraceAsString()}

_MESSAGE
            );

            throw $exception;
        }

        $this->stripeSource->refresh();

        $this->businessPaymentIntent->status = $this->stripeSource->status;

        $businessPaymentIntentData = $this->businessPaymentIntent->data;

        $businessPaymentIntentData['application_fee'] = $applicationFee->toArray();
        $businessPaymentIntentData['stripe']['source'] = $this->stripeSource->toArray();

        $this->businessPaymentIntent->data = $businessPaymentIntentData;

        $this->businessCharge->payment_provider = $this->businessPaymentProviderCode;
        $this->businessCharge->payment_provider_account_id = $this->businessPaymentIntent->payment_provider_account_id;
        $this->businessCharge->payment_provider_charge_type = $stripeCharge->object;
        $this->businessCharge->payment_provider_charge_id = $stripeCharge->id;
        $this->businessCharge->payment_provider_charge_method = $this->businessPaymentIntent->payment_provider_method;
        $this->businessCharge->payment_provider_transfer_type = 'application_fee';
        $this->businessCharge->status = $stripeCharge->status;
        $this->businessCharge->data = [
            'application_fee' => $applicationFee->toArray(),
            'stripe' => [
                'charge' => $stripeCharge->toArray(),
            ],
        ];
        $this->businessCharge->closed_at = $this->businessCharge->freshTimestamp();

        $stripeBalanceTransaction = Stripe\BalanceTransaction::retrieve($stripeCharge->balance_transaction);

        $this->businessCharge->home_currency = $stripeBalanceTransaction->currency;
        $this->businessCharge->home_currency_amount = $stripeBalanceTransaction->amount;
        $this->businessCharge->exchange_rate = $stripeBalanceTransaction->exchange_rate;
        $this->businessCharge->fixed_fee = $applicationFee->home_currency_fixed_fee_amount;
        $this->businessCharge->discount_fee_rate = $applicationFee->discount_fee_rate;
        $this->businessCharge->discount_fee = $applicationFee->home_currency_discount_fee_amount;

        if ($applicationFee->via_platform) {
            $this->businessCharge->commission_amount = (int) bcmul(
                $this->businessCharge->commission_rate,
                $this->businessCharge->amount,
            );

            $this->businessCharge->home_currency_commission_amount = (int) bcmul(
                $this->businessCharge->commission_rate,
                $this->businessCharge->home_currency_amount,
            );
        }

        $targetModel = $this->businessCharge->target;

        if ($targetModel instanceof Business\Order) {
            if ($targetModel->channel === Enumerations\Business\Channel::POINT_OF_SALE) {
                $targetModel->status = Enumerations\Business\OrderStatus::COMPLETED;
                $targetModel->closed_at = $targetModel->freshTimestamp();
            } else {
                $targetModel->status = Enumerations\Business\OrderStatus::REQUIRES_BUSINESS_ACTION;
            }
        }

        try {
            Facades\DB::transaction(function () use ($targetModel) {
                $this->businessPaymentIntent->save();
                $this->businessCharge->save();

                if ($targetModel instanceof Business\Order) {
                    $targetModel->save();
                    $targetModel->updateProductsQuantities();
                    $targetModel->notifyAboutNewOrder();
                }
            }, 3);
        } catch (Throwable $exception) {
            $exceptionClassName = get_class($exception);

            $this->log(<<<_MESSAGE
===========================
= ERROR  :  Saving Failed =
===========================

       Class  :  {$exceptionClassName}
    Location  :  {$exception->getFile()}:{$exception->getLine()}
     Message  :  {$exception->getMessage()}
     Remarks  :  Completing payment failed in our server, the processes should be already done in Stripe. Please check
                 and do reconciliation if required.

{$this->businessPaymentIntent->toJson()}

{$this->businessCharge->toJson()}

{$exception->getTraceAsString()}

_MESSAGE
            );

            throw $exception;
        }

        try {
            $stripeTransferGroup = Stripe\Transfer::all([
                'transfer_group' => $stripeCharge->transfer_group,
                'expand' => [ 'data.destination_payment' ],
            ]);

            foreach ($stripeTransferGroup->data as $stripeTransfer) {
                try {
                    $metadata = $stripeTransfer->destination_payment->metadata->toArray();

                    $metadata['platform'] = Facades\Config::get('app.name');
                    $metadata['version'] = ConfigurationRepository::get('platform_version');
                    $metadata['environment'] = Facades\Config::get('env');
                    $metadata['business_id'] = $this->business->getKey();
                    $metadata['charge_id'] = $this->businessCharge->getKey();

                    Stripe\Charge::update($stripeTransfer->destination_payment->id, compact('metadata'), [
                        'stripe_account' => $this->businessPaymentProvider->payment_provider_account_id,
                    ]);
                } catch (Throwable $exception) {
                    Facades\Log::error("Update the metadata of Stripe destination payment (ID : {$stripeTransfer->destination_payment->id}) failed. Error : {$exception->getMessage()}.");
                }
            }
        } catch (Throwable $exception) {
            Facades\Log::error("Failed to retrieve the Stripe transfer group for business (ID : {$this->business->getKey()}) failed. Error : {$exception->getMessage()}.");
        }
    }

    /**
     * Set the expected business charge ID, from the metadata of the Stripe source.
     *
     * @return void
     */
    protected function setExpectedBusinessChargeId() : void
    {
        $stripeSourceMetadataChargeId = $this->stripeSource->metadata->charge_id;

        if (is_null($stripeSourceMetadataChargeId)) {
            $this->expectedBusinessChargeId = 'undetected';
        } else {
            $stripeSourceMetadataChargeId = trim($stripeSourceMetadataChargeId);

            if (Str::length($stripeSourceMetadataChargeId) > 0) {
                $this->expectedBusinessChargeId = $stripeSourceMetadataChargeId;
            } else {
                $this->expectedBusinessChargeId = 'empty_string';
            }
        }
    }

    /**
     * Get the business payment intent relating to the Stripe source.
     *
     * @return void
     * @throws \App\Actions\Exceptions\BadRequest
     * @throws \App\Actions\Exceptions\UnexpectedError
     */
    protected function initiateBusinessPaymentIntent() : void
    {
        $this->businessPaymentIntent = Business\PaymentIntent::query()
            ->with([
                'business' => function (BelongsTo $query) {
                    $query->withoutGlobalScopes();
                },
                'charge' => function (BelongsTo $query) {
                    $query->withoutGlobalScopes();
                },
            ])
            ->where('payment_provider', $this->paymentProviderName)
            ->where('payment_provider_object_type', $this->stripeSource->object)
            ->where('payment_provider_object_id', $this->stripeSource->id)
            ->first();

        if (!( $this->businessPaymentIntent instanceof Business\PaymentIntent )) {
            $errorMessage = "The Stripe source (ID : {$this->stripeSource->id}) isn't match with any payment intent.";

            $this->log($errorMessage);

            throw new Exceptions\BadRequest($errorMessage);
        }

        $expectedBusinessPaymentIntentStatus = Stripe\Source::STATUS_PENDING;

        if ($this->businessPaymentIntent->status !== $expectedBusinessPaymentIntentStatus) {
            $this->log(<<<_MESSAGE
=============================
= WARNING  :  Invalid State =
=============================

The status of the business payment intent is invalid to continue.

     Current Status  :  {$this->businessPaymentIntent->status}
    Expected Status  :  {$expectedBusinessPaymentIntentStatus}
_MESSAGE
            );

            throw new Exceptions\BadRequest("The status of the business payment intent (ID : {$this->businessPaymentIntentId}) is currently `{$this->businessPaymentIntent->status}`, it must be in status `{$expectedBusinessPaymentIntentStatus}` to continue.");
        }

        $expectedStripeSourceStatus = Stripe\Source::STATUS_CHARGEABLE;

        if ($this->stripeSource->status !== $expectedStripeSourceStatus) {
            $this->log(<<<_MESSAGE
=============================
= WARNING  :  Invalid State =
=============================

The status of the Stripe source is invalid to continue.

    Current Status   :  {$this->stripeSource->status}
    Expected Status  :  {$expectedStripeSourceStatus}
_MESSAGE
            );

            throw new Exceptions\BadRequest("The status of the Stripe source (ID : {$this->stripeSource->id}) is currently `{$this->stripeSource->status}`, it must be in status `{$expectedStripeSourceStatus}` to continue.");
        }

        if ($this->businessPaymentIntent->payment_provider_method !== $this->stripeSource->type
            || $this->businessPaymentIntent->currency !== $this->stripeSource->currency
            || $this->businessPaymentIntent->amount !== $this->stripeSource->amount) {
            $this->log(<<<_MESSAGE
============================================
= WARNING  :  Stripe Source Data Unmatched =
============================================

The data of the Stripe source isn't match with the data of the payment intent.

    Payment Intent - ID      :  {$this->businessPaymentIntentId}
    Payment Intent - Method  :  {$this->businessPaymentIntent->payment_provider_method}
    Source - ID              :  {$this->stripeSource->id}
    Source - Method          :  {$this->stripeSource->object}
_MESSAGE
            );

            throw new Exceptions\UnexpectedError("The data of the Stripe source  (ID : {$this->stripeSource->id}) isn't match with the data of the payment intent (ID : $this->businessPaymentIntentId), check `{$this->logFilename}` for details.");
        }

        $this->businessPaymentIntentId = $this->businessPaymentIntent->getKey();
    }

    // = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = //
    //                                                                             //
    //          THE BELOW METHODS WERE OVERRIDDEN TO PREVENT THE RELATED           //
    //                          VALUES TO BE SET MANUALLY                          //
    //                                                                             //
    // = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = //

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function businessPaymentIntent(Business\PaymentIntent $businessPaymentIntent) : Action
    {
        throw new Exception('Setting business payment intent is prohibited in this class.');
    }
}
