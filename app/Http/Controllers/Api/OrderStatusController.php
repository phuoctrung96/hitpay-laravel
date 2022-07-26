<?php

namespace App\Http\Controllers\Api;

use App;
use Exception;
use App\Business;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use App\Business\PaymentIntent;
use App\Business\PaymentProvider;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Enumerations\OnboardingStatus;
use App\Enumerations\Business\ChargeStatus;
use App\Helpers\Shopee;

class OrderStatusController extends Controller
{
    /**
     * OrderStatusController constructor.
     */
    public function __construct()
    {
        //$this->middleware('auth:plugin');
    }

    public function status(Request $request, PaymentIntent $paymentIntent) {
      try {
        $maxDt = strtotime('+10 min', $paymentIntent->created_at->timestamp);
        $now = strtotime('now');
  
        // Only allow subsequent code execution if no more then 5 mins passed        
        if ($maxDt > $now) {
          $business = $paymentIntent->business;
   
          switch ($paymentIntent->payment_provider) {
            case PaymentProviderEnum::SHOPEE_PAY:
              $provider = $business->paymentProviders()
                ->where([
                  'payment_provider' => PaymentProviderEnum::SHOPEE_PAY,
                  'onboarding_status' => OnboardingStatus::SUCCESS
                ])->first();
  
              if ($provider instanceof PaymentProvider) {
                $res = Shopee::postRequest('/v3/merchant-host/transaction/check', [
                  'transaction_type' => 13,  
                  'reference_id' => $paymentIntent->payment_provider_object_id,
                  'merchant_ext_id' => $provider->payment_provider_account_id,
                  'store_ext_id' => $provider->data['sid'],
                  'amount' => $paymentIntent->amount                
                ]);
  
                if ($res->errcode === 0 && $res->debug_msg === 'success') {
                  Shopee::confirmOrder(
                    $business,
                    $paymentIntent,
                    $res->transaction
                  );

                  return Response::json([
                    'status' => 'success'
                  ]);
                }
        
              } else {
                // Provider not onboarded, actually this should not happen
                throw new Exception('Wrong payment provider');  
              }
  
              break;
    
            default:
              throw new Exception('Unsupported payment provider');
          }  
    
        } else {
          // Expired
          throw new Exception('Timed out');
        }
  
      } catch (Exception $exception) {
        return Response::json([
          'status' => 'error',
          'message' => $exception->getMessage()
        ]);
      }
    }
}