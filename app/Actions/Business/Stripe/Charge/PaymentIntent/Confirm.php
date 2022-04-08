<?php

namespace App\Actions\Business\Stripe\Charge\PaymentIntent;

use App\Actions\Business\Charges\InitiateRelatableUsingPaymentIntent;
use App\Actions\Business\Stripe\Charge\Action;
use App\Actions\Business\Stripe\Charge\BusinessPaymentIntentValidator;
use App\Actions\UseLogViaStorage;
use App\Business;
use App\Enumerations;
use App\Logics\ConfigurationRepository;
use Illuminate\Support\Facades;
use Stripe;
use Throwable;

class Confirm extends Action
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
            $businessPaymentIntent->payment_provider_method,
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
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function process() : Business\PaymentIntent
    {
        $this->validateBusinessPaymentIntent('card');

        $stripePaymentIntent = Stripe\PaymentIntent::retrieve($this->businessPaymentIntent->payment_provider_object_id);

        if ($stripePaymentIntent->status === 'requires_confirmation') {
            $stripePaymentIntent = $stripePaymentIntent->confirm();
        }

        $this->businessPaymentIntent->status = $stripePaymentIntent->status;

        $businessPaymentIntentData = $this->businessPaymentIntent->data;

        $businessPaymentIntentData['stripe']['payment_intent'] = $stripePaymentIntent->toArray();

        $this->businessPaymentIntent->data = $businessPaymentIntentData;

        if ($this->businessPaymentIntent->status !== 'succeeded') {
            $this->businessPaymentIntent->save();

            return $this->businessPaymentIntent;
        }

        $stripeCharge = $stripePaymentIntent->charges->first();

        $this->businessCharge->payment_provider = $this->businessPaymentProviderCode;
        $this->businessCharge->payment_provider_account_id = $this->businessPaymentIntent->payment_provider_account_id;
        $this->businessCharge->payment_provider_charge_type = $stripeCharge->object;
        $this->businessCharge->payment_provider_charge_id = $stripeCharge->id;
        $this->businessCharge->payment_provider_charge_method = $this->businessPaymentIntent->payment_provider_method;
        $this->businessCharge->payment_provider_transfer_type = 'application_fee';
        $this->businessCharge->status = $stripeCharge->status;

        $businessChargeData = $this->businessCharge->data;

        $businessChargeData['application_fee'] = $this->businessPaymentIntent->data['application_fee'];
        $businessChargeData['stripe']['charge'] = $stripeCharge->toArray();

        $this->businessCharge->data = $businessChargeData;

        $this->businessCharge->closed_at = $this->businessCharge->freshTimestamp();

        $stripeBalanceTransaction = Stripe\BalanceTransaction::retrieve($stripeCharge->balance_transaction);

        $this->businessCharge->home_currency = $stripeBalanceTransaction->currency;
        $this->businessCharge->home_currency_amount = $stripeBalanceTransaction->amount;
        $this->businessCharge->exchange_rate = $stripeBalanceTransaction->exchange_rate;
        $this->businessCharge->fixed_fee = $this->businessPaymentIntent->data['application_fee']['breakdown']['home_currency']['fixed_fee_amount'];
        $this->businessCharge->discount_fee_rate = $this->businessPaymentIntent->data['application_fee']['discount_fee_rate'];
        $this->businessCharge->discount_fee = $this->businessPaymentIntent->data['application_fee']['breakdown']['home_currency']['discount_fee_amount'];

        if ($this->businessPaymentIntent->data['application_fee']['via_platform']) {
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

        return $this->businessPaymentIntent;
    }
}
