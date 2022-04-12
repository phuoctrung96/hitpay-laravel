<?php

namespace App\Http\Controllers\Shop;

use App\Business;
use App\Business\PaymentIntent;
use App\Business\PaymentProvider;
use App\Business\Transfer;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Enumerations\OnboardingStatus;
use App\Http\Controllers\Controller;
use App\Jobs\Providers\GrabPay\SaveCompletedCharge;
use App\Notifications\AlertPayNowAccountChanged;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Helpers\Zip;
use App\Enumerations\Business\ChargeStatus;

class ZipController extends Controller
{
    public function handleRedirect (Request $request) {
      try {
        // result - approved |  cancelled
        // checkoutId - checkoutId
        $paymentIntent = PaymentIntent::where([
          'payment_provider_object_type' => 'payment_intent',
          'payment_provider_object_id' => $request->query('checkoutId'),
          'payment_provider' => PaymentProviderEnum::ZIP
        ])->first();

        if ($paymentIntent instanceof PaymentIntent) {
          $business = $paymentIntent->business()->first();
          $charge = $paymentIntent->charge;

          $provider = $business
            ->paymentProviders
            ->where('payment_provider', PaymentProviderEnum::ZIP)
            ->first();

          if ($provider instanceof PaymentProvider) {
            DB::beginTransaction();

            try {
              switch ($request->query('result')) {
                case 'approved':
                  $res = Zip::postRequest('charges', [
                    'authority' => [
                      'type' => 'checkout_id',
                      'value' => $paymentIntent->payment_provider_object_id
                    ],
                    'amount' => floor( $charge->amount * 100 ) / 10000,
                    'currency' => 'SGD'
                  ], 200);

                  switch ($res->state) {
                    case 'approved':
                      $paymentIntent->status = 'succeeded';
                      $paymentIntent->save();
    
                      // Charge - success
                      $data = $charge->data ? $charge->data : [];                    
                      $data['zip_receipt_number'] = $res->receipt_number;
                      $data['zip_charges_id'] = $res->id;
                      $charge->data = $data;            
    
                      $charge->payment_provider = $paymentIntent->payment_provider;
                      $charge->payment_provider_account_id = $paymentIntent->payment_provider_account_id;
                      $charge->payment_provider_charge_type = $paymentIntent->payment_provider_object_type;
                      $charge->payment_provider_charge_id = $paymentIntent->payment_provider_object_id;
                      $charge->payment_provider_charge_method = $paymentIntent->payment_provider_method;
    
                      [
                        $fixedAmount,
                        $percentage,
                      ] = $provider->getRateFor(
                          $business->country, $business->currency, $charge->currency, $charge->channel,
                          $charge->payment_provider_charge_method
                      );    
    
                      $charge->fixed_fee = $fixedAmount;
                      $charge->discount_fee_rate = $percentage;
                      $charge->discount_fee = bcmul($charge->discount_fee_rate, $charge->home_currency_amount);  
    
                      $charge->payment_provider_transfer_type = 'wallet';
                      $charge->status = ChargeStatus::SUCCEEDED;
                      $charge->exchange_rate = 1;
                      $charge->closed_at = $charge->freshTimestamp();
    
                      $charge->save();
                      DB::commit();
                      
                      // success
                      if ($charge->paymentRequest->redirect_url) {
                        return redirect()->away($charge->paymentRequest->redirect_url . '?reference=' . $charge->paymentRequest->id . '&status=completed');    
                      } else {
                        return redirect()->route('securecheckout.payment.request.completed', ['p_charge' => $charge->getKey()]);    
                      }

                    case 'captured': 
                    case 'authorised': 
                      // Currently we does not support this types
                      Log::critical('[Zip] Unsupported status from /charges: ' . $res->status . ', checkout id: ' . $request->query('checkoutId'));

                      if ($charge->paymentRequest->redirect_url) {
                        return redirect()->away($charge->paymentRequest->redirect_url . '?reference=' . $charge->paymentRequest->id . '&status=failed');
                      } else {
                        return Response::view('shop.checkout.simpleerror', ['message' => 'Payment failed']);                    
                      } 
                  }

                case 'cancelled':
                  $charge->status = ChargeStatus::CANCELED;
                  $charge->save();
                  DB::commit();
                  return Response::view('shop.checkout.simpleerror', ['message' => 'Payment cancelled']);
              }            
            } catch (Throwable $exception) {
              DB::rollBack();
              throw $exception;      
            }          
          }
        } else {
          // Unknown checkout Id
          Log::critical('[Zip] Redirect with unknown checkout id: ' . $request->query('checkoutId'));
          return Response::view('shop.checkout.simpleerror', ['message' => 'Payment failed']);
        }

      } catch (\Throwable $exception) {
        if ($exception instanceof \GuzzleHttp\Exception\ClientException) {
          // by default Guzzle truncates error message
          Log::critical('[Zip] Error in redirect: ' . $exception->getResponse()->getBody()->getContents());
        } else {
          Log::critical('[Zip] Error in redirect: ' . $exception->getMessage());
        }
        
        return Response::view('shop.checkout.simpleerror', ['message' => 'Payment failed']);
      }
    }
}
