<?php

namespace App\Actions\Business\DBS\ICN;

use App\Business;
use App\Business\Order;
use App\Business\PaymentIntent;
use App\Enumerations\Business\Channel;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\OrderStatus;
use App\Enumerations\PaymentProvider;
use App\Jobs\RefundForPaymentIntent;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ForCharge extends Action
{
    public function process()
    {
        /** @var \App\Business\PaymentIntent $paymentIntent */
        $paymentIntent = PaymentIntent::where('payment_provider', PaymentProvider::DBS_SINGAPORE)
            ->where('payment_provider_object_type', 'inward_credit_notification')
            ->where('payment_provider_object_id', $this->reference)
            ->first();

        if ($paymentIntent === null) {
            $errorReference[] = "reference '{$this->reference}'";

            if (isset($this->data['header']['msgId'])) {
                $errorReference[] = "message ID '{$this->data['header']['msgId']}'";
            }

            $errorReference = join(', ', $errorReference);

            Log::info("The paynow {$errorReference} might not come from this server. please check file: {$this->filepath}");

            return false;
        }

        // We move this here so that we can get the updated data stored whenever we update the payment intent in any
        // exception.
        //
        $data = $paymentIntent->data;

        $data['filename'] = $this->filepath;

        // If isset "$data['response']", which means this payment intent is already triggered once. But to make sure
        // no data is missing (e.g. same DICN, but different transaction reference ID), we will store it here.
        //
        if (isset($data['response'])) {
            $data['duplicates'][] = $this->data;
        } else {
            $data['response'] = $this->data;
        }

        $paymentIntent->data = $data;

        // We check if the response contains this information, this is critical, and we need to make sure the payment
        // received is matching the request. And for the transaction reference ID, it is used to check if the payment
        // has been processed, e.g. when queue job retrying, or DBS sends the ICN again. If either one of them
        // missing, log and return.
        //
        if (!isset(
            $this->data['txnInfo']['amtDtls']['txnCcy'],
            $this->data['txnInfo']['amtDtls']['txnAmt'],
            $this->data['txnInfo']['txnRefId']
        )) {
            $message = "The paynow (payment intent: {$paymentIntent->id}) have no 'txnRefId', currency and amount in ICN. Please check file: {$this->filepath}";

            $this->logAndSavePaymentIntent($paymentIntent, $message);

            Log::critical($message);

            return false;
        }

        $paymentIntentAmount = getReadableAmountByCurrency($paymentIntent->currency, $paymentIntent->amount);

        if (strtolower($this->data['txnInfo']['amtDtls']['txnCcy']) !== strtolower($paymentIntent->currency)
            || ( (float) $this->data['txnInfo']['amtDtls']['txnAmt'] ) !== $paymentIntentAmount) {
            $this->refundInvalidPayment(
                $paymentIntent,
                $this->data,
                $this->filepath,
                "The paynow (payment intent: {$paymentIntent->id}) have different currency or amount in ICN and payment intent. Please check file: {$this->filepath}, auto refund will be triggered."
            );

            return false;
        }

        // If the payment intent (same ICN) is succeeded, we will create a log and send to slack, and if they are
        // having different txnRefId, we will do an auto refund.
        //
        if ($paymentIntent->status === 'succeeded') {
            if ($paymentIntent->additional_reference === $this->data['txnInfo']['txnRefId']) {
                $message = "The paynow (payment intent: {$paymentIntent->id}) is succeeded, the transaction reference ID is same, no auto refund will be triggered. Additional information can check file: {$this->filepath}.";

                $this->logAndSavePaymentIntent($paymentIntent, $message);

                Log::alert($message);
            } else {
                $this->refundInvalidPayment(
                    $paymentIntent,
                    $this->data,
                    $this->filepath,
                    "The paynow (payment intent: {$paymentIntent->id}) is succeeded, the existing transaction reference ID : {$paymentIntent->additional_reference} is different with the incoming one : {$this->data['txnInfo']['txnRefId']}, an auto refund will be triggered. Additional information can check the file: {$this->filepath}."
                );
            }

            return false;
        }

        // If the scenario is not fell in any above, we will mark the payment intent as succeeded. And then only we
        // check if the charge is succeeded.
        //
        $paymentIntent->status = 'succeeded';
        $paymentIntent->additional_reference = $this->data['txnInfo']['txnRefId'];

        $paymentIntent->save();

        /** @var \App\Business\Charge $charge */
        $charge = $paymentIntent->charge;

        // IF the charge is already succeeded, and current payment intent isn't succeeded it is most probably having
        // another payment intent which is succeeded, it can be PayNow or any others. Hence, we will do auto refund
        // too.
        //
        if ($charge->isSucceeded()) {
            // TODO - KEEP IN VIEW
            //   ------------------->>>
            //   -
            //   For the auto refund happened above, the status of the payment intent remain the same, pending will
            //   be still pending, succeeded will be still succeeded, that is correct. But for here, the payment
            //   intent status will still be "succeeded" after refunded, it seems correct and also seems incorrect, so
            //   we keep in view for now.
            //
            $this->refundInvalidPayment(
                $paymentIntent,
                $this->data,
                $this->filepath,
                "The payment intent is marked as succeeded, but the charge is already succeeded when the status of payment intent changed.",
                "The charge : {$charge->id} (payment intent : {$paymentIntent->id}) is succeeded, but receiving an ICN : {$this->reference}. Not sure what is happening here, possible that the charge has been paid by another method or there's another success ICN. An auto refund will be triggered now. Please check check file: {$this->filepath} and decide what to do next."
            );

            return false;
        }

        $business = $paymentIntent->business;

        if ($charge->platform_business_id) {
            $platformModel = Business::find($charge->platform_business_id);

            if ($platformModel) {
                $paymentProviderModel = $platformModel->paymentProviders()
                    ->where('payment_provider', PaymentProvider::DBS_SINGAPORE)
                    ->first();

                if ($paymentProviderModel) {
                    $hasPlatformProvider = true;
                } else {
                    Log::critical("Platform ID '{$charge->platform_business_id}' provided but no payment provider found.");
                }
            } else {
                Log::critical("Platform ID '{$charge->platform_business_id}' provided but no business found.");
            }
        }

        /** @var \App\Business\PaymentProvider $paymentProviderModel */
        if (!isset($paymentProviderModel)) {
            $paymentProviderModel = $business->paymentProviders()->where([
                'payment_provider' => PaymentProvider::DBS_SINGAPORE,
            ])->first();
        }

        if (!$paymentProviderModel) {
            $message = "Look what? The server detected a business without DBS enabled but able to make payment via PayNow. Please check if anything missed out when collecting payment.\n";

            $message .= "    Business - Payment Provider : ".$paymentIntent->payment_provider.";\n";
            $message .= "    Business - ID : ".$paymentIntent->getKey();

            Log::critical($message);

            Storage::append($this->filepath, "\n\n".$message);
        }

        $cashback = $charge->business()->first()->getRegularCashback($charge)->first();

        if ($charge->business_id !== $paymentIntent->business_id) {
            $message = "Again? The business charge model and the business payment intent model are having different business ID. Please find out how can this happened!\n";

            $message .= "    Charge - ID : ".$charge->id.";\n";
            $message .= "    Charge - Business ID : ".$charge->business_id.";\n";
            $message .= "    Payment Intent - ID : ".$paymentIntent->id.";\n";
            $message .= "    Payment Intent - Business ID : ".$paymentIntent->business_id;

            Log::critical($message);

            Storage::append($this->filepath, "\n\n".$message);
        }

        $charge->payment_provider = $paymentIntent->payment_provider;
        $charge->payment_provider_account_id = $paymentIntent->payment_provider_account_id;
        $charge->payment_provider_charge_type = $paymentIntent->payment_provider_object_type;
        $charge->payment_provider_charge_id = $paymentIntent->payment_provider_object_id;
        $charge->payment_provider_charge_method = $paymentIntent->payment_provider_method;
        $charge->payment_provider_transfer_type = 'wallet';
        $charge->status = ChargeStatus::SUCCEEDED;
        $charge->data = $this->data;
        $charge->closed_at = $charge->freshTimestamp();

        if ($paymentProviderModel) {
            [
                $fixedAmount,
                $percentage,
            ] = $paymentProviderModel->getRateFor(
                $business->country, $business->currency, $charge->currency, $charge->channel,
                $charge->payment_provider_charge_method, null, null, $charge->amount
            );

            if ($cashback) {
                $fixedAmount += $cashback->cashback_admin_fee;
            }

            $charge->home_currency = $charge->currency;
            $charge->home_currency_amount = $charge->amount;
            $charge->exchange_rate = 1;
            $charge->fixed_fee = $fixedAmount;
            $charge->discount_fee_rate = $percentage;
            $charge->discount_fee = bcmul($charge->discount_fee_rate, $charge->home_currency_amount);

            if ($hasPlatformProvider ?? false) {
                $charge->commission_amount = bcmul($charge->commission_rate, $charge->amount);
                $charge->home_currency_commission_amount = bcmul(
                    $charge->commission_rate,
                    $charge->home_currency_amount
                );
            }
        }

        $targetModel = $charge->target;

        if ($targetModel instanceof Order) {
            if ($targetModel->channel === Channel::POINT_OF_SALE) {
                $targetModel->status = OrderStatus::COMPLETED;
                $targetModel->closed_at = $targetModel->freshTimestamp();
            } else {
                $targetModel->status = OrderStatus::REQUIRES_BUSINESS_ACTION;
            }
        }

        try {
            DB::transaction(function () use ($paymentIntent, $charge, $targetModel) {
                $charge->save();

                if ($targetModel instanceof Order) {
                    $targetModel->save();
                    $targetModel->updateProductsQuantities();
                    $targetModel->notifyAboutNewOrder();
                }
            }, 3);
        } catch (Throwable $exception) {
            $message = "Completing payment failed in our server, the processes should be already done in DBS. Please do reconciliation now.\n";

            $message .= "    Charge - ID : ".$charge->id.";\n";
            $message .= "    Charge - Business ID : ".$charge->business_id.";\n";
            $message .= "    Payment Intent - ID : ".$paymentIntent->id.";\n";
            $message .= "    Payment Intent - Business ID : ".$paymentIntent->business_id.";\n";
            $message .= "    Related files: ".$this->filepath;

            Log::critical($message);

            $message .= "\n";
            $message .= json_encode($paymentIntent->toArray(), JSON_PRETTY_PRINT)."\n";
            $message .= json_encode($charge->toArray(), JSON_PRETTY_PRINT)."\n";

            Storage::append($this->filepath, "\n\n".$message);

            throw $exception;
        }

        return true;
    }

    /**
     * Log the event and save payment intent.
     *
     * @param  \App\Business\PaymentIntent  $paymentIntent
     * @param  string  $message
     *
     * @return \App\Business\PaymentIntent
     */
    private function logAndSavePaymentIntent(PaymentIntent $paymentIntent, string $message) : PaymentIntent
    {
        $data = $paymentIntent->data;

        $data['remarks'][] = [
            'timestamp' => Date::now()->toDateTimeString(),
            'message' => $message,
        ];

        $paymentIntent->data = $data;
        $paymentIntent->save();

        return $paymentIntent;
    }

    /**
     * Do refund for PAYMENT INTENT (not charge) when the scenario is
     *
     * 1. Payment intent is succeeded, but different transaction reference ID; or
     * 2. Charge is already succeeded; or
     * 3. The currency of payment intent is not matching the currency of the inward credit notification.
     *
     * @param  \App\Business\PaymentIntent  $paymentIntent
     * @param  array  $content
     * @param  string  $filename
     * @param  string  $message
     * @param  string|null  $logMessage
     */
    private function refundInvalidPayment(
        Business\PaymentIntent $paymentIntent, array $content, string $filename, string $message,
        string $logMessage = null
    ) : void {
        $this->logAndSavePaymentIntent($paymentIntent, $message);

        // We will refund after 10 minutes. Because sometimes the bank side is not yet updated and can't find the
        // payment.
        //
        RefundForPaymentIntent::dispatch($paymentIntent, $content, $filename)->delay(Date::now()->addMinutes(10));

        Log::critical($logMessage ?: $message);
    }
}
