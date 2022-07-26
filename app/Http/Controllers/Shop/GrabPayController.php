<?php

namespace App\Http\Controllers\Shop;

use App\Business\PaymentIntent;
use App\Enumerations\Business\ChargeStatus;
use App\Exceptions\GrabPayException;
use App\Helpers\GrabPay;
use App\Http\Controllers\Controller;
use App\Jobs\Providers\GrabPay\SaveCompletedCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class GrabPayController extends Controller
{
    public function handleRedirect (Request $request) {
      try {
        // Check state first
        $paymentIntents = PaymentIntent::where('additional_reference', $request->query('state'))->get();

        if (count($paymentIntents) > 0) {
          if (count($paymentIntents) > 1) {
            Log::critical('[GrabPay] Duplicate value in additional_reference column: ' . $request->query('state'));
          }

          $paymentIntent = $paymentIntents[0];

          // Handle redirect
          if ($request->has('error')) {
            throw new GrabPayException('GrabPay redirect returned with error: ' . $request->get('error'));
          } else {
            $business = $paymentIntent->business()->first();
            $charge = $paymentIntent->charge;

            /*
            $provider = $business
              ->paymentProviders
              ->where('payment_provider', PaymentProviderEnum::GRABPAY)
              ->first();

            if ($provider instanceof PaymentProvider) {
            */
              // get OAuth token
              $res = GrabPay::postRequest('/grabid/v1/oauth2/token', [
                'code' => $request->query('code'),
                'client_id' => config('services.grabpay.client_id'),
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('services.grabpay.redirect_uri'),
                'code_verifier' => $paymentIntent->data['code_verifier'],
                'client_secret' => config('services.grabpay.client_secret'),
              ]);

              // confirm payment
              $timestamp = time();
              $signLine = $timestamp . $res->access_token;
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
              $date = gmdate("D, d M Y H:i:s") . " GMT";

              $confirmRes = GrabPay::postRequest('/grabpay/partner/v2/charge/complete', [
                'partnerTxID' => $paymentIntent->payment_provider_object_id
              ], [
                'Authorization' => 'Bearer ' . $res->access_token,
                'X-GID-AUX-POP' => $sign,
                'Date' => $date
              ]);

              $message = "Headers\n" .
                "Authorization: Bearer " . $res->access_token . "\n" .
                "X-GID-AUX-POP: " . $sign . "\n" .
                "Date: " . $date . "\n\n" .
                "Query\n" .
                "partnerTxID: " . $paymentIntent->payment_provider_object_id . "\n\n" .
                "Reply\n\n";

              foreach ($confirmRes as $key => $value) {
                $message = $message . $key . ': ' . $confirmRes->$key . "\n";
              }

              SaveCompletedCharge::dispatch($message);

              DB::beginTransaction();

              try {
                switch ($confirmRes->txStatus) {
                  case 'success':
                    // Payment intent - success
                    $paymentIntent->status = 'succeeded';
                    $paymentIntent->save();

                    // Charge - success
                    $data = $charge->data ? $charge->data : [];
                    $data['grabpay_transaction_sn'] = $confirmRes->txID;
                    $data['payment_method'] = $confirmRes->paymentMethod;
                    $data['access_token'] = $res->access_token;
                    $charge->data = $data;

                    $charge->payment_provider = $paymentIntent->payment_provider;
                    $charge->payment_provider_account_id = $paymentIntent->payment_provider_account_id;
                    $charge->payment_provider_charge_type = $paymentIntent->payment_provider_object_type;
                    $charge->payment_provider_charge_id = $paymentIntent->payment_provider_object_id;
                    $charge->payment_provider_charge_method = $paymentIntent->payment_provider_method;

                    $charge->payment_provider_transfer_type = 'wallet';
                    $charge->status = ChargeStatus::SUCCEEDED;
                    $charge->exchange_rate = 1;
                    $charge->closed_at = $charge->freshTimestamp();

                    $charge->save();
                    DB::commit();

                      $redirectUrl = $charge->paymentRequest->getRedirectUrl([
                          'status' => 'completed',
                          'reference' => $charge->paymentRequest->id,
                      ]);

                      if (!is_null($redirectUrl)) {
                          return redirect()->away($redirectUrl);
                      } else {
                          return redirect()->route('securecheckout.payment.request.completed', [
                              'p_charge' => $charge->getKey(),
                          ]);
                      }

                  case 'failed':
                    $charge->status = ChargeStatus::FAILED;

                    $charge->save();
                    DB::commit();

                    Log::critical('[GrabPay] Order confirmation failed, charge: ' . $charge->id);

                      $redirectUrl = $charge->paymentRequest->getRedirectUrl([
                          'status' => 'failed',
                          'reference' => $charge->paymentRequest->id,
                      ]);

                      if (!is_null($redirectUrl)) {
                          return redirect()->away($redirectUrl);
                      } else {
                          return Response::view('shop.checkout.simpleerror', [ 'message' => 'Payment failed' ]);
                      }

                  default:
                    break;
                }

              } catch (Throwable $exception) {
                DB::rollBack();
                throw $exception;
              }
            /*
            } else {
              throw new GrabPayException("The server detected a business without GrabPay enabled but able to make payment via" .
                " GrabPay. Please check if anything missed out when collecting payment.\n" .
                " Charge ID : " . $charge->id);
            }
            */
          }
        } else {
          throw new GrabPayException('Can not find payment intent using state value: ' . $request->query('state'));
        }

      } catch (\Throwable $exception) {
        if ($exception instanceof \GuzzleHttp\Exception\RequestException) {
          // by default Guzzle truncates error message
          Log::info('[GrabPay] Error in redirect: ' . $exception->getResponse()->getBody()->getContents());
        } else {
          Log::info('[GrabPay] Error in redirect: ' . $exception->getMessage());
        }

        return Response::view('shop.checkout.simpleerror', ['message' => 'Payment failed']);
      }
    }
}
