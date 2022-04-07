<?php

namespace App\Helpers;

use Throwable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Exceptions\ShopeeException;
use App\Business\PaymentIntent;
use App\Enumerations\Business\ChargeStatus;

/**
 * Class Shopee
 * @package App\Helpers
 */
class Shopee
{
    /**
     * @param $endpoint
     * @param $data
     * @param $success
     * @return Array
     */
    public static function postRequest($endpoint, $data, $success = 200)
    {
      $localData = $data;
      $localData['request_id'] = Str::uuid()->toString();

      $requestData = json_encode($localData);
      Log::info('Shopee Request Data '.$requestData);
      $hash = base64_encode(hash_hmac(
        'sha256',
        $requestData,
        config('services.shopee.secret_key'),
        true // binary
      ));

      $client = new Client([
        'base_uri' => 'https://' . config('services.shopee.domain')
      ]);

      try {
        $res = $client->post($endpoint, [ 
          'body' => $requestData,
          'headers' => [
            'X-Airpay-ClientId' => config('services.shopee.client_id'),
            'X-Airpay-Req-H' => $hash
          ]
        ]);
      } catch (ClientException $exception) {
        throw new ShopeeException('HTTP exception');
      }

      if ($res->getStatusCode() === $success) {
        Log::info('Shopee Response Data '.$res->getBody());
        $res = json_decode((string) $res->getBody());

        if (isset($res->errcode) && $res->errcode > 0) {
          $msg = 'Shopee API failed with error ' . $res->errcode . ': ' . $res->debug_msg;
          Log::critical($msg);
          throw new ShopeeException($msg);  
        } else {
          return $res;
        }
      } else {
        $msg = 'Shopee API failed with HTTP code: ' . $res->getStatusCode();
        Log::critical($msg);
        throw new ShopeeException($msg);
      }
    }

    public static function confirmOrder (
      $business, 
      $paymentProvider, 
      $paymentIntent,
      $shopeeData
    ) {
      // Only allow for pending payments
      if ($paymentIntent->status === 'pending') {
        $charge = $paymentIntent->charge;

        DB::beginTransaction();

        try {
          // Payment intent - success
          $paymentIntent->status = 'succeeded';
          $paymentIntent->save();
  
          // Charge - success
          $data = $charge->data ? $charge->data : [];
          $data['shopee_transaction_sn'] = $shopeeData->transaction_sn;
          $data['shopee_user_id_hash'] = $shopeeData->user_id_hash;
          $charge->data = $data;    
  
          $charge->payment_provider = $paymentIntent->payment_provider;
          $charge->payment_provider_account_id = $paymentIntent->payment_provider_account_id;
          $charge->payment_provider_charge_type = $paymentIntent->payment_provider_object_type;
          $charge->payment_provider_charge_id = $paymentIntent->payment_provider_object_id;
          $charge->payment_provider_charge_method = $paymentIntent->payment_provider_method;
  
          if ($paymentProvider) {
            [
              $fixedAmount,
              $percentage,
            ] = $paymentProvider->getRateFor(
                $business->country, $business->currency, $charge->currency, $charge->channel,
                $charge->payment_provider_charge_method
            );    
  
            $charge->fixed_fee = $fixedAmount;
            $charge->discount_fee_rate = $percentage;
            $charge->discount_fee = bcmul($charge->discount_fee_rate, $charge->home_currency_amount);  
          } else {
            Log::critical("[SHOPEE] Look what? The server detected a business without Shopee Pay enabled but able to make payment via" .
              " Shopee Pay. Please check if anything missed out when collecting payment.\n" .
              " Charge ID : " . $charge->id);
          }
  
          $charge->payment_provider_transfer_type = 'wallet';
          $charge->status = ChargeStatus::SUCCEEDED;
          $charge->exchange_rate = 1;
          $charge->closed_at = $charge->freshTimestamp();                  
          $charge->save();
  
          DB::commit();
        } catch (Throwable $exception) {
          DB::rollBack();
          throw $exception;      
        }  
      } else {
        // Charge not pending
        throw new ShopeeException('Can only confirm payment in pending status');
      }
    }
}
