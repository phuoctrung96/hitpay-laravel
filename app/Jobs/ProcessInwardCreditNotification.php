<?php

namespace App\Jobs;

use App\Actions\Business\DBS\ICN\ForCharge;
use App\Business;
use App\Business\Refund;
use App\Business\RefundIntent;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\PaymentProvider;
use App\Notifications\Business\NotifyPayNowRefundFailed;
use App\Notifications\NotifyAdminAboutFailedRefund;
use Crypt_GPG;
use Exception;
use HitPay\PayNow\Fast;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProcessInwardCreditNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $body;

    public $filename;

    /**
     * Create a new job instance.
     *
     * @param string $body
     * @param string $filename
     */
    public function __construct(string $body, string $filename)
    {
        $this->body = $body;
        $this->filename = $filename;
    }

    /**
     * @throws \Throwable
     */
    public function handle()
    {
        try {
            $gpg = new Crypt_GPG([
                'homedir' => '/home/ubuntu/.gnupg',
                'digest-algo' => 'SHA256',
                'cipher-algo' => 'AES256',
                'compress-algo' => 'zip',
                'debug' => Config::get('app.debug'),
            ]);

            $signKey = $gpg->importKey(file_get_contents(storage_path('dbs.key')));
            $gpg->addSignKey($signKey['fingerprint']);

            $decrypt = $gpg->importKey(file_get_contents(storage_path('private.key')));
            $gpg->addDecryptKey($decrypt['fingerprint']);

            $content = $gpg->decryptAndVerify($this->body);
            $content = json_decode($content['data'], true);

            if (isset($content['txnInfo']['customerReference'])) {
                $reference = $filename = $content['txnInfo']['customerReference'];
            } else {
                $reference = $filename = 'unknown-request';
            }

            $filename = 'paynow' . DIRECTORY_SEPARATOR . now()->toDateString()
                . DIRECTORY_SEPARATOR . $filename . '-' . microtime(true) . '.txt';

            Storage::append($filename, json_encode($content, JSON_PRETTY_PRINT));
        } catch (Throwable $exception) {
            $errorPath = 'paynow-error' . DIRECTORY_SEPARATOR . $this->filename . '-' . microtime(true) . '.txt';

            Storage::append($errorPath, $this->body);

            if (isset($filename)) {
                Log::critical('Decryption failed for PayNow webhook, reference files: ' . $filename . ' and ' . $errorPath);
            } else {
                Log::critical('Decryption failed for PayNow webhook, reference file: ' . $errorPath);
            }

            throw $exception;
        }

        if ($reference === 'unknown-request') {
            Log::critical('No reference detected in PayNow request, please check file: ' . $filename);

            return false;
        }

        if (Str::startsWith($reference, 'DICNR')) {
            return $this->processRefund($reference, $content, $filename);
        } elseif (Str::startsWith($reference, 'DICNT')) {
            return $this->processTopUp($reference, $content, $filename);
        }

        ForCharge::withReference($reference)->filepath($filename)->data($content)->process();

        return true;
    }

    protected function processRefund(string $reference, array $content, string $filename)
    {
        /** @var \App\Business\RefundIntent $refundIntent */
        $refundIntent = RefundIntent::where('payment_provider', PaymentProvider::DBS_SINGAPORE)
            ->where('payment_provider_object_type', 'inward_credit_notification')
            ->where('payment_provider_object_id', $reference)
            ->first();

        if ($refundIntent === null) {
            if (isset($content['header']['msgId'])) {
                $errorReference = 'message ID \'' . $content['header']['msgId'] . '\'';
            } else {
                $errorReference = 'reference \'' . $reference . '\'';
            }

            Log::info('The paynow refund ' . $errorReference . ' might not come from this server. please check file: '
                . $filename);

            return false;
        }

        if (isset($content['txnInfo']['amtDtls']['txnCcy'], $content['txnInfo']['amtDtls']['txnAmt'])) {
            $refundIntentAmount = getReadableAmountByCurrency($refundIntent->currency, $refundIntent->amount);

            if (strtolower($content['txnInfo']['amtDtls']['txnCcy']) !== strtolower($refundIntent->currency)
                || ((float)$content['txnInfo']['amtDtls']['txnAmt']) < $refundIntentAmount) {
                Log::critical('The paynow refund (refund intent: ' . $refundIntent->id . ') have different currency or'
                    . ' lesser than amount in ICN and refund intent. Please check file: ' . $filename);

                return false;
            }
        } else {
            Log::critical('The paynow refund (refund intent: ' . $refundIntent->id . ') have no currency and amount in ICN.'
                . ' Please check file: ' . $filename);
        }

        if (!Str::startsWith($refundIntent->payment_provider_account_id, 'proxy:')) {
            Log::critical('The paynow refund (refund intent: ' . $refundIntent->id . ') is not returning a proxy number.'
                . ' Please check file: ' . $filename);

            return false;
        }

        $refundIntent->status = 'succeeded';

        $data = $refundIntent->data;

        $data['response'] = $content; // Keep the QR value

        $refundIntent->data = $data;

        $charge = $refundIntent->charge;

        if ($charge->business_id !== $refundIntent->business_id) {
            $message = "The business charge model and the business payment intent model are having different business"
                . " ID. Please find out how can this happened!\n"
                . "    Charge - ID : " . $charge->id . ";\n"
                . "    Charge - Business ID : " . $charge->business_id . ";\n"
                . "    Payment Intent - ID : " . $refundIntent->id . ";\n"
                . "    Payment Intent - Business ID : " . $refundIntent->business_id;

            Log::critical($message);

            Storage::append($filename, "\n\n" . $message);
        }

        $balance = $charge->balance ?? $charge->amount;

        $amount = getRealAmountForCurrency(strtolower($content['txnInfo']['amtDtls']['txnCcy']), $content['txnInfo']['amtDtls']['txnAmt']);

        $refundIntent->status = 'succeeded';

        $refund = new Refund;
        $refund->id = Str::orderedUuid()->toString();
        $refund->business_charge_id = $charge->getKey();
        $refund->payment_provider = $refundIntent->payment_provider;
        $refund->payment_provider_account_id = $refundIntent->payment_provider_account_id;
        $refund->payment_provider_refund_method = $refundIntent->payment_provider_method;
        $refund->amount = $refundIntent->amount;

        $fastData = Fast::new($refund->getKey(), 'PPP')
            ->setAmount(getReadableAmountByCurrency($refundIntent->currency, $refund->amount))
            ->setBusinessName($charge->business->getName())
            ->setReceiverEmail($charge->customer_email)
            ->setReceiverName($charge->data['txnInfo']['senderParty']['name'] ?? $charge->customer_email)
            ->setReceiverAccountNumber(str_replace('proxy:', '', $refundIntent->payment_provider_account_id))
            ->generate();

        if ($fastData->getErrorMessages()) {
            Log::critical(implode("\n", $fastData->getErrorMessages())
                . "\n\n"
                . "============================================================="
                . "=                                                           ="
                . "=  When you see this message, which means we have received  ="
                . "=  the fund from merchant, but the fast transfer failed     ="
                . "=  due to the above reason.                                 ="
                . "=                                                           ="
                . "============================================================="
                . "\n\n");
        }

        $fastDataResponse = $fastData->getResponse();

        if (is_array($fastDataResponse)) {
            $fastDataResponseInString = json_encode($fastDataResponse, JSON_PRETTY_PRINT);
        } elseif (is_string($fastDataResponse)) {
            $fastDataResponseInString = $fastDataResponse;
        } else {
            $fastDataResponseInString = gettype($fastDataResponse);
        }

        // Not sure if the success response change, they only mentioned response for RJCT.
        if (isset($fastDataResponse['txnResponse']['txnStatus']) &&
            $fastDataResponse['txnResponse']['txnStatus'] === 'ACTC') {
            $refund->payment_provider_refund_id = $fastDataResponse['txnResponse']['txnRefId'];
            $refund->payment_provider_refund_type = 'fast';
            $refund->remark = 'succeeded';

            if ($balance - $amount <= 0) {
                $charge->status = ChargeStatus::REFUNDED;
                $charge->balance = null;
                $charge->closed_at = $charge->freshTimestamp();

                if ($balance - $amount <= 0) {
                    try {
                        Log::critical('Charge # ' . $charge->getKey() . ' has balance of '
                            . getFormattedAmount($charge->currency, $balance) . ' to be refunded, but we received '
                            . getFormattedAmount($refundIntent->currency, $refundIntent->amount));
                    } catch (Throwable $exception) {
                        Log::critical($exception->getMessage() . "\n" . $exception->getTraceAsString());
                    }
                }
            } else {
                $charge->balance = $balance - $amount;
            }
        } else {
            $refund->payment_provider_refund_id = $refundIntent->payment_provider_object_id;
            $refund->payment_provider_refund_type = $refundIntent->payment_provider_object_type;
            $refund->remark = Str::limit("Rejected \n" . implode("\n", $fastData->getErrorMessages()), 240, '');
            $refund->status = 'failed';
        }

        $data['requests'][] = [
            'body' => $fastData->getRequestBody(),
            'response' => $fastDataResponse,
        ];

        $refund->data = $data;

        try {
            DB::transaction(function () use ($charge, $refundIntent, $refund) {
                $charge->save();
                $refundIntent->save();
                $refund->save();
            });

            if ($refund->status === 'failed') {
                try {
                    $charge->business->notify(new NotifyPayNowRefundFailed($charge, $refund->id, $refund->amount));

                    if ($slack = config('services.slack.failed_refunds')) {
                        Notification::route('slack', $slack)
                            ->notify(new NotifyAdminAboutFailedRefund($charge, $refund, $refundIntent, $reference,
                                $fastDataResponseInString));
                    } else {
                        throw new Exception('The Slack webhook is not set for failed refunds.');
                    }
                } catch (Throwable $throwable) {
                    Log::critical(get_class($throwable)
                        . "\n"
                        . $throwable->getFile() . ':' . $throwable->getLine()
                        . "\n"
                        . $throwable->getMessage()
                        . "\n\n"
                        . "============================================================="
                        . "=                                                           ="
                        . "=  When you see this message, which means we have received  ="
                        . "=  the fund from merchant, and the refund is rejected by    ="
                        . "=  DBS, but we failed to send the failed notification.      ="
                        . "=                                                           ="
                        . "=  Check filename " . $refund->getKey() . "      ="
                        . "=                                                           ="
                        . "============================================================="
                        . "\n\n");
                }
            }
        } catch (Throwable $exception) {
            Log::critical(get_class($exception)
                . "\n"
                . $exception->getFile() . ':' . $exception->getLine()
                . "\n"
                . $exception->getMessage()
                . "\n\n"
                . "============================================================="
                . "=                                                           ="
                . "=  When you see this message, which means we have received  ="
                . "=  the fund from merchant, and the fast transfer can be     ="
                . "=  already success, but we failed to update the database.   ="
                . "=  Check filename " . $refund->getKey() . "      ="
                . "=                                                           ="
                . "============================================================="
                . "\n\n");

            throw $exception;
        }

        return true;
    }

    protected function processTopUp(string $reference, array $content, string $filename)
    {
        /** @var \App\Business\Wallet\TopUpIntent $topUpIntent */
        $topUpIntent = Business\Wallet\TopUpIntent::where('payment_provider', PaymentProvider::DBS_SINGAPORE)
            ->where('payment_provider_object_type', 'inward_credit_notification')
            ->where('payment_provider_object_id', $reference)
            ->first();

        if (!( $topUpIntent instanceof Business\Wallet\TopUpIntent )) {
            $errorReference[] = "reference '{$reference}'";

            if (isset($content['header']['msgId'])) {
                $errorReference[] = "message ID '{$content['header']['msgId']}'";
            }

            $errorReference = join(', ', $errorReference);

            Log::info("The paynow top up to wallet {$errorReference} might not come from this server. please check file: {$filename}");

            return false;
        }

        if (!isset(
            $content['txnInfo']['amtDtls']['txnCcy'],
            $content['txnInfo']['amtDtls']['txnAmt'],
            $content['txnInfo']['txnRefId']
        )) {
            Log::alert("The paynow (top up intent: {$topUpIntent->id}) have no 'txnRefId', currency and amount in ICN. Please check file: {$filename}");

            return false;
        }

        if ($topUpIntent->status === 'succeeded') {
            if ($topUpIntent->additional_reference === $content['txnInfo']['txnRefId']) {
                $message = "The paynow (top up intent: {$topUpIntent->id}) is succeeded and the transaction reference ID is same. You can ignore this.";
            } else {
                $message = "The paynow (top up intent: {$topUpIntent->id}) is succeeded, the existing transaction reference ID : {$topUpIntent->additional_reference} is different with the incoming one. Double payment occurred, no top up is made. Additional information can check the file: {$filename}.";
            }

            Log::alert($message);

            return false;
        }

        $topUpIntentAmount = getReadableAmountByCurrency($topUpIntent->currency, $topUpIntent->amount);

        if (strtolower($content['txnInfo']['amtDtls']['txnCcy']) !== strtolower($topUpIntent->currency)
            || ( (float) $content['txnInfo']['amtDtls']['txnAmt'] ) < $topUpIntentAmount) {
            Log::critical("The paynow top up to wallet (top up intent: {$topUpIntent->id}) have different currency or lesser than amount in ICN and top up intent. Please check file: {$filename}");

            return false;
        }

        $topUpIntent->additional_reference = $content['txnInfo']['txnRefId'];
        $topUpIntent->status = 'succeeded';

        $data = $topUpIntent->data;

        $data['response'] = $content;

        $topUpIntent->data = $data;
        $topUpIntent->status = 'succeeded';

        DB::transaction(function () use ($topUpIntent) {
            $topUpIntent->save();
        }, 3);

        $topUpIntent->business->topUp($topUpIntent->currency, $topUpIntent->amount, 'Top up via PayNow');

        return true;
    }
}
