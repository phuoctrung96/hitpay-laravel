<?php

namespace App\Jobs;

use App\Business\PaymentIntent;
use App\Models\Business\Charge\AutoRefund;
use GuzzleHttp\Exception\ClientException;
use HitPay\DBS;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RefundForPaymentIntent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Business\PaymentIntent
     */
    public PaymentIntent $paymentIntent;

    /**
     * @var array
     */
    public array $content;

    /**
     * @var string
     */
    public string $filename;

    /**
     * Create a new job instance.
     *
     * @param  \App\Business\PaymentIntent  $paymentIntent
     * @param  array  $content
     * @param  string  $filename
     */
    public function __construct(PaymentIntent $paymentIntent, array $content, string $filename)
    {
        $this->paymentIntent = $paymentIntent;
        $this->content = $content;
        $this->filename = $filename;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $autoRefundData['filename'] = $this->filename;
        $autoRefundData['original_response'] = $this->content;

        $autoRefund = new AutoRefund;

        $autoRefund->business_id = $this->paymentIntent->business_id;
        $autoRefund->business_charge_id = $this->paymentIntent->business_charge_id;

        $autoRefund->paymentIntent()->associate($this->paymentIntent);

        $transactionInfo = $this->content['txnInfo'];
        $transactionId = $transactionInfo['txnRefId'];
        $amountDetails = $transactionInfo['amtDtls'];
        $transactionCurrency = $amountDetails['txnCcy'];
        $transactionAmount = $amountDetails['txnAmt'];

        $autoRefund->payment_provider = $this->paymentIntent->payment_provider;
        $autoRefund->currency = strtolower($transactionCurrency);
        $autoRefund->amount = getRealAmountForCurrency($autoRefund->currency, (float) $transactionAmount);
        $autoRefund->status = 'pending';
        $autoRefund->data = $autoRefundData;

        $autoRefund->additional_reference = $transactionId;

        $autoRefund->save();

        // The parameters are the auto refund record ID, followed by the transaction reference ID returned by DBS for
        // successful payment, and the ICN.
        //
        // TODO - KEEP IN VIEW
        //   ------------------->>>
        //   -
        //   There is no retry for this part, which means once the refund failed, it failed. Anyway we will still
        //   having an auto refund record which was created up here.
        //
        $refundData = DBS\Refund::new(
            $autoRefund->getKey(),
            $transactionId,
            $this->paymentIntent->payment_provider_object_id,
        );

        $refundData->setOriginalCurrency($transactionCurrency);
        $refundData->setOriginalAmount($transactionAmount);
        $refundData->setRefundableAmount($transactionAmount);
        $refundData->setRefundAmount($transactionAmount);
        $refundData->setMessage('Refund for duplicate payment');

        // TODO - KEEP IN VIEW
        //   ------------------->>>
        //   -
        //   We handle only "ACTC" and "PDNG" status for auto refund. The status "PDNG" status will trigger a
        //   notification to Slack too, just like the status "ACTC", but no further update will be made later.
        //   -
        //   For now, we are not going to check the auto refund with status "PDNG" whether is succeeded or failed. We
        //   will have to find out how can we get the latest status and update this.

        $message = "Please find the related file with name starts with {$autoRefund->getKey()} in refund directory.";

        $requestResponse['body'] = $refundData->getRequestBody();

        try {
            $refundData = $refundData->process();
        } catch (ClientException $exception) {
            $requestResponse['response'] = $exception->getResponse()->getBody()->getContents();

            $autoRefundData['request'][] = $requestResponse;

            $autoRefund->data = $autoRefundData;

            $autoRefund->save();

            Storage::append($this->filename, "\n\n".json_encode($requestResponse)."\n\n{$message}");

            Log::critical("The paynow (charge: {$this->paymentIntent->business_charge_id}, payment intent: {$this->paymentIntent->id}) refunding failed. Please find related file with name starts with {$autoRefund->getKey()} in refund directory for details.");

            throw $exception;
        }

        $refundDataResponse = $refundData->getResponseBody();

        if (isset($refundDataResponse['txnResponse']['txnStatus'])) {
            $autoRefund->payment_provider_refund_id = $refundDataResponse['txnResponse']['txnRefId'] ?? 'unknown';
            $autoRefund->payment_provider_refund_type = 'max';

            if ($refundDataResponse['txnResponse']['txnStatus'] === 'ACTC') {
                $autoRefund->status = 'refunded';
                $autoRefund->refunded_at = $autoRefund->freshTimestamp();
            } elseif ($refundDataResponse['txnResponse']['txnStatus'] === 'PDNG') {
                // TODO - 20211003
                //   --------------->>>
                //   -
                //   We should find some way to check from DBS and update this status.
                //
                $autoRefund->status = 'pending_refund';
                $autoRefund->refunded_at = $autoRefund->freshTimestamp();
            } else {
                $autoRefund->status = 'failed';

                $remarks = "Failed, unknown status \"{$refundDataResponse['txnResponse']['txnStatus']}\" received. ";
                $remarks .= $refundDataResponse['txnResponse']['txnStatusDescription'] ?? 'No additional description';

                $autoRefundData['remarks'][] = [
                    'timestamp' => Date::now()->toDateTimeString(),
                    'message' => $remarks,
                ];
            }
        } else {
            $autoRefund->status = 'undetected';
        }

        $requestResponse['response'] = $refundDataResponse;

        $autoRefundData['request'][] = $requestResponse;

        $autoRefund->data = $autoRefundData;

        $autoRefund->save();

        Storage::append($this->filename, "\n\n".json_encode($requestResponse)."\n\n{$message}");

        if ($autoRefund->status === 'refunded' || $autoRefund->status === 'pending_refund') {
            Log::info("The paynow (charge: {$this->paymentIntent->business_charge_id}, payment intent: {$this->paymentIntent->id}), the existing transaction reference ID : {$this->paymentIntent->additional_reference} and the incoming one : {$transactionId} is refunded or pending from bank.");
        } else {
            Log::critical("The paynow (charge: {$this->paymentIntent->business_charge_id}, payment intent: {$this->paymentIntent->id}), the existing transaction reference ID : {$this->paymentIntent->additional_reference} and the incoming one : {$transactionId} is having an unknown status `{$autoRefund->status}`.");
        }
    }
}
