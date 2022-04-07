<?php

namespace App\Jobs\Wallet;

use Throwable;
use App\Business\Refund as Model;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\PaymentProvider;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Helpers\Shopee;
use App\Helpers\GrabPay;
use App\Helpers\Zip;

class Refund implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Business\Refund
     */
    public $refund;

    /**
     * Create a new job instance.
     */
    public function __construct(Model $refund)
    {
        $this->refund = $refund;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if ($this->refund->payment_provider !== PaymentProvider::DBS_SINGAPORE &&
            $this->refund->payment_provider !== PaymentProvider::SHOPEE_PAY &&
            $this->refund->payment_provider !== PaymentProvider::GRABPAY &&
            $this->refund->payment_provider !== PaymentProvider::ZIP) {
            Log::critical(sprintf('A payment provider for refund requested is yet to be supported, please check refund intent ID : %s',
                $this->refund->getKey()));

            return;
        }

        if (!App::isProduction() && $this->refund->payment_provider === PaymentProvider::DBS_SINGAPORE) {
            $this->refund->status = null;
            $this->refund->remark = 'succeeded';
            $this->refund->save();
            return;
        }

        $charge = $this->refund->charge;

        $refundableAmount = (is_int($charge->balance) ? $charge->balance : 0) + $this->refund->amount;

        switch ($this->refund->payment_provider) {
          case PaymentProvider::DBS_SINGAPORE:
            // {
            //     "data": "00020101021226550009SG.PAYNOW010120210201605883W030100414202107010010465204000053037025405....",
            //     "method": "paynow_online",
            //     "response": {
            //         "header": {
            //             "ctry": "SG",
            //             "msgId": "IMB3196663013300000000C977480869161",
            //             "orgId": "BEACHUS3",
            //             "timeStamp": "2021-07-01T00:06:40.005"
            //         },
            //         "txnInfo": {
            //             "amtDtls": {
            //                 "txnAmt": 315,
            //                 "txnCcy": "SGD"
            //             },
            //             "txnDate": "2021-07-01",
            //             "txnType": "INWARD PAYNOW",
            //             "valueDt": "2021-07-01",
            //             "txnRefId": "21070100063999218745",
            //             "senderParty": {
            //                 "name": "ISLAM MD SOFIQUL",
            //                 "senderBankId": "DBSSSGSGXXX"
            //             },
            //             "receivingParty": {
            //                 "name": "HITPAY PAYMENT      SOLUTIONS P",
            //                 "accountNo": "0720172418"
            //             },
            //             "customerReference": "DICNP16250689460367KKLPKM"
            //         }
            //     },
            //     "object_type": "inward_credit_notification"
            // }

            // TODO - Have to confirm if $charge->data['response']['txtInfo']['txnRefId'] is the correct one.
            // payment_provider_charge_id

            // \HitPay\DBS\Refund::new($static, 'IRGPPSG200121A0000418', 'RP-3d69cb5fb73247cebfb0767e5816f751')
            //     ->setOriginalCurrency('sgd')
            //     ->setOriginalAmount(1888.80)
            //     ->setRefundableAmount(1888.80)
            //     ->setRefundAmount(8.80)
            //     ->process()
            //     ->getResponseBody()

            $refundData = \HitPay\DBS\Refund::new(
              $this->refund->getKey(),
              $charge->payment_provider_charge_type === PaymentMethodType::COLLECTION
                  ?  $charge->payment_provider_charge_id
                  : $charge->data['txnInfo']['txnRefId'],
              $charge->payment_provider_charge_type === PaymentMethodType::COLLECTION
                  ? $charge->data['txnResponse']['customerReference']
                  : $charge->payment_provider_charge_id
              )
              ->setOriginalCurrency($charge->currency)
              ->setOriginalAmount(getReadableAmountByCurrency($charge->currency, $charge->amount))
              ->setRefundableAmount(getReadableAmountByCurrency($charge->currency, $refundableAmount))
              ->setRefundAmount(getReadableAmountByCurrency($charge->currency, $this->refund->amount))
              ->setMessage(sprintf(
                  'Refund %s for Charge #%s',
                  getFormattedAmount($charge->currency, $this->refund->amount),
                  $charge->getKey()
              ));

            try {
                $refundData = $refundData->process();
            } catch (ClientException $exception) {
                $responseBody = $exception->getResponse()->getBody()->getContents();

                $this->refund->status = 'failed';
                $this->refund->remark = Str::limit("Rejected \n{$responseBody}", 240, '');

                $data = $this->refund->data;

                $data['requests'][] = [
                    'body' => $refundData->getRequestBody(),
                    'response' => $responseBody,
                ];

                $this->refund->data = $data;

                $this->refund->save();
                $this->refund->revert();

                return;
            } catch (ServerException $exception) {
                $bodyContent = $exception->getResponse()->getBody()->getContents();

                Log::info("{$bodyContent}\n\n".json_encode($this->refund->toArray(), 128))."\n\n{$exception->getTraceAsString()}";
            }

            $refundDataResponse = $refundData->getResponseBody();

            if (isset($refundDataResponse['txnResponse']['txnStatus'])) {
                if ($refundDataResponse['txnResponse']['txnStatus'] === 'ACTC') {
                    $this->refund->payment_provider_refund_id = $refundDataResponse['txnResponse']['txnRefId'];
                    $this->refund->payment_provider_refund_type = 'max';
                    $this->refund->status = null;
                    $this->refund->remark = 'succeeded';
                } elseif ($refundDataResponse['txnResponse']['txnStatus'] === 'PDNG') {
                    $this->refund->payment_provider_refund_id = $refundDataResponse['txnResponse']['txnRefId'];
                    $this->refund->payment_provider_refund_type = 'max';
                } else {
                    $this->refund->status = 'failed';
                    $this->refund->remark = Str::limit("Rejected, Reason: Status not ACTC nor PDNG but {$refundDataResponse['txnResponse']['txnStatus']} \n".( $refundDataResponse['txnResponse']['txnStatusDescription'] ?? 'Unknown' ),
                            240, '');
                }
            } else {
                $this->refund->status = 'failed';
                $this->refund->remark = Str::limit("Rejected, Reason: Undetected status\n"
                    .($refundDataResponse['txnResponse']['txnStatusDescription'] ?? 'Unknown'), 240, '');
            }

            $data = $this->refund->data;

            $data['requests'][] = [
                'body' => $refundData->getRequestBody(),
                'response' => $refundDataResponse,
            ];

            $this->refund->data = $data;

            break;

          case PaymentProvider::SHOPEE_PAY:
            Log::critical('Shopee refund job 1');

            // Get provider
            $provider = $charge->business->paymentProviders()->where('payment_provider', PaymentProvider::SHOPEE_PAY)->first();

            if ($provider) {
              Log::critical('Shopee refund job 2');

              $refundData = [
                // Shopee limits reference_id field to 25 chrs so we can not pass full uuid
                // From other side, shopee requires payment_reference_id to be unique
                // So we compact uuid and take first 25 chrs of it and pass whole charge id
                // as additional info
                'payment_reference_id' => $charge->payment_provider_charge_id,
                'transaction_type' => 15,
                'refund_reference_id' => $this->refund->id,
                'merchant_ext_id' => $provider->payment_provider_account_id,
                'store_ext_id' => $provider->data['sid'],
                'amount' => $charge->amount
              ];

              // Shopee support only full-refund
              try {
                $res = Shopee::postRequest('/v3/merchant-host/transaction/refund/create', $refundData);

                if ($res->errcode === 0) {
                  $this->refund->status = 'succeeded';
                } else {
                  $this->refund->status = 'failed';
                  $this->refund->remark = 'Shopee refund request failed, errcode: ' . $res->errcode . ', message: ' . $res->debug_msg;
                }
  
                // Store request
                $data = $this->refund->data;
  
                $data['requests'][] = [
                    'body' => $refundData,
                    'response' => $res,
                ];
  
                $this->refund->data = $data;
  
              } catch (Throwable $error) {
                $this->refund->status = 'failed';
                $this->refund->remark = 'Shopee refund request failed, message: ' . $error->getMessage();
              }

            } else {
              $this->refund->status = 'failed';
              $this->refund->remark = 'Can not find payment provider for refund request';
              Log::critical('Can not find payment provider for refund request, charge id: ' . $charge->id . ', refund request id: ' . $this->refund->id);
            }

            break;

          case PaymentProvider::GRABPAY:
            Log::critical('GrabPay refund job');

            // We need charge, payment provider and payment intent objects
            $charge = $this->refund->charge;
            $provider = $charge->business->paymentProviders()->where('payment_provider', PaymentProvider::GRABPAY)->first();

            $timestamp = time();
            $signLine = $timestamp . $charge->data['access_token'];
            $sign = GrabPay::urlsafe_base64encode(hash_hmac(
              'sha256',
              $signLine,
              config('services.grabpay.client_secret'),
              true // binary
            ));

            $payload = [
              'time_since_epoch' => $timestamp,
              'sig' => $sign
            ];

            $sign = GrabPay::urlsafe_base64encode(json_encode($payload));

            $refundData = [
              'partnerGroupTxID' => $charge->payment_provider_charge_id,
              'partnerTxID' => $charge->payment_provider_charge_id,
              'amount' => $this->refund->amount,
              'currency' => 'SGD',
              'merchantID' => $provider->data['merchant_id'],
              'originTxID' => $charge->data['grabpay_transaction_sn']
            ];

            $res = GrabPay::postRequest('/grabpay/partner/v2/refund', $refundData, [
              'Authorization' => 'Bearer ' . $charge->data['access_token'],
              'X-GID-AUX-POP' => $sign,
              'Date' => gmdate("D, d M Y H:i:s") . ' GMT'
            ]);

            // Set refund status
            if ($res->txStatus === 'success') {
              $this->refund->status = 'succeeded';
            } else {
              $this->refund->status = 'failed';
              $this->refund->remark = 'GrabPay refund request failed, reason: ' . $res->reason;
            }

            // Store request
            $data = $this->refund->data;

            $data['requests'][] = [
                'body' => $refundData,
                'response' => $res,
            ];

            $this->refund->data = $data;

            break;
        
          case PaymentProvider::ZIP:
            // We need charge, payment provider and payment intent objects
            $charge = $this->refund->charge;
            $provider = $charge->business->paymentProviders()->where('payment_provider', PaymentProvider::ZIP)->first();

            $refundData = [
              'charge_id' => $charge->data['zip_charges_id'],
              'amount' => floor( $this->refund->amount * 100 ) / 10000,
              'reason' => 'Refund request'
            ];

            try {
              $res = Zip::postRequest('refunds', $refundData, 200);

              $data = $this->refund->data;

              $data['requests'][] = [
                  'body' => $refundData,
                  'response' => $res,
              ];
  
              $this->refund->data = $data;  
                
            } catch (\Throwable $exception) {
              if ($exception instanceof \GuzzleHttp\Exception\ClientException) {
                // by default Guzzle truncates error message
                Log::critical('[Zip] Error in refund job: ' . $exception->getResponse()->getBody()->getContents());
              } else {
                Log::critical('[Zip] Error in refund job: ' . $exception->getMessage());
              }
            }

            break;
        }

        $this->refund->save();

        if ($this->refund->status === 'failed') {
            $this->refund->revert();
        }
    }
}
