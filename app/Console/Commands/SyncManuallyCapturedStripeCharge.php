<?php

namespace App\Console\Commands;

use App\Business;
use App\Business\Charge;
use App\Enumerations;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\PaymentMethodType;
use App\Logics\ConfigurationRepository;
use App\Providers\AppServiceProvider;
use Exception;
use HitPay\Data\PaymentProviders;
use Illuminate\Console\Command;
use Illuminate\Support\Facades;
use Stripe;
use Throwable;

class SyncManuallyCapturedStripeCharge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:sync-captured-charge {charge_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync the charge which is manually captured on Stripe dashboard.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $chargeId = $this->argument('charge_id');

        $charge = Charge::find($chargeId);

        if (!$charge instanceof Charge) {
            return $this->returnError('The charge ID is not exist.');
        }

        if ($charge->status !== ChargeStatus::REQUIRES_PAYMENT_METHOD) {
            return $this->returnError("This charge is not in status 'requires_payment_method', but '{$charge->status}'.");
        }

        /** @var \App\Business $business */
        $business = $charge->business()->firstOrFail();

        if ($business->currency !== $charge->currency) {
            return $this->returnError("This charge has different currency with the business, ask developer to do this.");
        }

        $paymentIntents = $charge->paymentIntents()->get();

        $succeededPaymentIntents = $paymentIntents->where('status', 'succeeded');
        $succeededPaymentIntentsCount = $succeededPaymentIntents->count();

        if ($succeededPaymentIntentsCount > 0) {
            $succeededPaymentIntentStripeIds = $succeededPaymentIntents
                ->pluck('payment_provider_object_id')
                ->join('\', \'', '\' and \'');

            return $this->returnError(
                "This charge is already having {$succeededPaymentIntentsCount} succeeded payment intents ('{$succeededPaymentIntentStripeIds}'), but the charge status is not succeeded. Please contact developers."
            );
        }

        $cardPresentedPaymentIntent = $paymentIntents
            ->where('payment_provider_method', PaymentMethodType::CARD_PRESENT);

        if ($cardPresentedPaymentIntent->count() === 0) {
            return $this->returnError(
                "No card presented payment intent found for this charge."
            );
        }

        if ($cardPresentedPaymentIntent->count() > 1) {
            $paymentIntentId = $this->choice(
                'Which payment intent you want to proceed with?',
                $cardPresentedPaymentIntent->pluck('payment_provider_object_id')->toArray()
            );
        } else {
            $selectedCardPresentedPaymentIntent = $cardPresentedPaymentIntent->first();

            $paymentIntentId = $selectedCardPresentedPaymentIntent->payment_provider_object_id;
        }

        $this->info("Got payment intent: {$paymentIntentId}");

        /** @var \App\Business\PaymentIntent $selectedPaymentIntent */
        $selectedPaymentIntent = $cardPresentedPaymentIntent
            ->where('payment_provider_object_id', $paymentIntentId)
            ->first();

        $businessPaymentProvider = $business
            ->paymentProviders()
            ->where('payment_provider', $selectedPaymentIntent->payment_provider)
            ->firstOrFail();

        $this->info('Got payment provider.');

        /** @var \HitPay\Data\Countries\Objects\PaymentProvider $stripeSpecs */
        $stripeSpecs = PaymentProviders::all()->where('code', $selectedPaymentIntent->payment_provider)->first();

        $stripeSecret = Facades\Config::get("services.stripe.{$stripeSpecs->getCountry()}.secret");

        if (is_null($stripeSecret)) {
            throw new Exception("The configuration for payment provider '{$businessPaymentProvider->payment_provider}' isn't available. PLEASE CHECK IMMEDIATELY.");
        }

        Stripe\Stripe::setApiKey($stripeSecret);
        Stripe\Stripe::setApiVersion(AppServiceProvider::STRIPE_VERSION);

        $stripePaymentIntent = Stripe\PaymentIntent::retrieve($selectedPaymentIntent->payment_provider_object_id, [
            'charges.data.payment_method',
        ]);

        $this->info('Got Stripe payment intent.');

        if ($stripePaymentIntent->status !== 'succeeded') {
            throw new Exception("You can only sync succeeded Stripe payment intent.");
        }

        $selectedPaymentIntent->status = $stripePaymentIntent->status;

        $businessPaymentIntentData = $selectedPaymentIntent->data;

        $businessPaymentIntentData['application_fee'] = [
            "home_currency" => $business->currency,
            "settlement_currency" => $charge->currency,
            "exchange_rate" => 1.0,
            "discount_fee_rate" => 0,
            "breakdown" => [
                "home_currency" => [
                    "fixed_fee_amount" => 0,
                    "discount_fee_amount" => 0,
                    "total_amount" => 0,
                ],
                "settlement_currency" => [
                    "fixed_fee_amount" => 0,
                    "discount_fee_amount" => 0,
                    "total_amount" => 0,
                ],
            ],
            "via_platform" => false,
        ];

        $businessPaymentIntentData['stripe']['payment_intent'] = $stripePaymentIntent->toArray();

        $selectedPaymentIntent->data = $businessPaymentIntentData;

        $selectedPaymentIntent->save();

        $this->info('Saved payment intent.');

        /** @var \Stripe\Charge $stripeCharge */
        $stripeCharge = $stripePaymentIntent->charges->first();

        $stripeChargeCreatedAt = Facades\Date::createFromTimestamp($stripeCharge->created);

        $charge->payment_provider = $selectedPaymentIntent->payment_provider;
        $charge->payment_provider_account_id = $selectedPaymentIntent->payment_provider_account_id;
        $charge->payment_provider_charge_type = $stripeCharge->object;
        $charge->payment_provider_charge_id = $stripeCharge->id;
        $charge->payment_provider_charge_method = $selectedPaymentIntent->payment_provider_method;
        $charge->payment_provider_transfer_type = 'application_fee';
        $charge->status = $stripeCharge->status;

        $businessChargeData = $charge->data;

        $businessChargeData['application_fee'] = $selectedPaymentIntent->data['application_fee'];
        $businessChargeData['stripe']['charge'] = $stripeCharge->toArray();

        $charge->data = $businessChargeData;

        $charge->closed_at = $stripeChargeCreatedAt;

        $stripeBalanceTransaction = Stripe\BalanceTransaction::retrieve($stripeCharge->balance_transaction);

        $applicationFee = $selectedPaymentIntent->data['application_fee'];
        $applicationFeeHomeCurrencyBreakdown = $applicationFee['breakdown']['home_currency'];

        $charge->home_currency = $stripeBalanceTransaction->currency;
        $charge->home_currency_amount = $stripeBalanceTransaction->amount;
        $charge->exchange_rate = $stripeBalanceTransaction->exchange_rate;
        $charge->fixed_fee = $applicationFeeHomeCurrencyBreakdown['fixed_fee_amount'];
        $charge->discount_fee_rate = $applicationFee['discount_fee_rate'];
        $charge->discount_fee = $applicationFeeHomeCurrencyBreakdown['discount_fee_amount'];

        $targetModel = $charge->target;

        if ($targetModel instanceof Business\Order) {
            if ($targetModel->channel === Enumerations\Business\Channel::POINT_OF_SALE) {
                $targetModel->status = Enumerations\Business\OrderStatus::COMPLETED;
                $targetModel->closed_at = $stripeChargeCreatedAt;
            } else {
                $targetModel->status = Enumerations\Business\OrderStatus::REQUIRES_BUSINESS_ACTION;
            }

            $this->info('Got target');
        }

        Facades\DB::transaction(function () use ($charge, $targetModel) {
            $charge->save();

            if ($targetModel instanceof Business\Order) {
                $targetModel->save();
                $targetModel->updateProductsQuantities();
                $targetModel->notifyAboutNewOrder();
            }
        }, 3);

        $this->info('Try to update destination payment.');

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
                    $metadata['environment'] = Facades\Config::get('app.env');
                    $metadata['business_id'] = $business->getKey();
                    $metadata['charge_id'] = $charge->getKey();

                    Stripe\Charge::update($stripeTransfer->destination_payment->id, compact('metadata'), [
                        'stripe_account' => $businessPaymentProvider->payment_provider_account_id,
                    ]);
                } catch (Throwable $exception) {
                    $this->warn(
                        "Failed to update the metadata of Stripe destination payment (ID : {$stripeTransfer->destination_payment->id}). Error : {$exception->getMessage()}."
                    );
                }
            }
        } catch (Throwable $exception) {
            $this->warn("Failed to retrieve the Stripe transfer group. Error : {$exception->getMessage()}.");
        }

        $this->info('DONE');

        return true;
    }

    protected function returnError(string $message) : bool
    {
        $this->error($message);

        return false;
    }
}
