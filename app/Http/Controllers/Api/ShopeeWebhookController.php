<?php

namespace App\Http\Controllers\Api;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Business\PaymentIntent;
use App\Business\PaymentProvider;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\OnboardingStatus;
use App\Helpers\Shopee;
use App\Exceptions\ShopeeException;

class ShopeeWebhookController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function __invoke(Request $request)
    {
      if ($request->hasHeader('X-Airpay-Req-H')) {        
        // !!!
        Log::critical("[SHOPEE] WebHook received");

        $body = $request->getContent();

        Storage::put('shopee-request'.DIRECTORY_SEPARATOR . md5($body) . '.txt', $body);

        // Check signature
        $hash = base64_encode(hash_hmac(
          'sha256',
          $body,
          config('services.shopee.secret_key'),
          true // binary
        ));

        if ($hash === $request->header('X-Airpay-Req-H')) {
          $jsonBody = json_decode($body);

          $paymentIntent = PaymentIntent::where([
            'payment_provider_object_type' => 'inward_credit_notification',
            'payment_provider_object_id' => $jsonBody->payment_reference_id
          ])->first();

          if ($paymentIntent instanceof PaymentIntent) {
            if ($paymentIntent->charge->amount === $jsonBody->amount) {
              $business = $paymentIntent->business;

              $provider = $business->paymentProviders()
                ->where([
                  'payment_provider' => PaymentProviderEnum::SHOPEE_PAY,
                  'onboarding_status' => OnboardingStatus::SUCCESS
                ])->first();

              try {
                Shopee::confirmOrder(
                  $business,
                  $provider,
                  $paymentIntent,
                  $jsonBody
                );
              } catch (ShopeeException $exception) {
                Log::critical("[SHOPEE] " . $exception->getMessage());
              }

            } else {
              Log::critical("[SHOPEE] Amount mismatch in callback, payment_reference_id = " . $jsonBody->payment_reference_id . ", charge amount: " . $charge->amount . ", callback amount: " . $jsonBody->amount);  
            }

          } else {
            Log::critical("[SHOPEE] Payment intent not found in callback, payment_reference_id = " . $jsonBody->payment_reference_id);
          }

        } else {
          // Hash mismatch
          Log::critical("[SHOPEE] Callback signature mismatch.\nHeader sig: " . $request->header('X-Airpay-Req-H') . "\nOur sig:" . $hash . "\nBody: " .  $body);
        }        

        return Response::json();  
      } else {
        // wrong headers    
        Log::critical("[SHOPEE] No X-Airpay-Req-H in request");    
      }
    }
}
