<?php

namespace App\Http\Controllers\Shop;

use App\Business\PaymentIntent;
use App\Enumerations\Business\ChargeStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

// Used for Stripe FPX & Stripe GrabPay
class FpxController extends Controller
{
    public function handleRedirect (Request $request, string $paymentIntentId) {
      $paymentIntent = PaymentIntent::findOrFail($paymentIntentId);
      $charge = $paymentIntent->charge;
      $business = $paymentIntent->business()->first();

      if ($request->query->has('noWait')) {
        if ($charge->status === ChargeStatus::SUCCEEDED) {
            if ($charge->paymentRequest) {
                $redirectUrl = $charge->paymentRequest->getRedirectUrl([
                    'status' => 'completed',
                    'reference' => $charge->paymentRequest->id,
                ]);
            }

            if (isset($redirectUrl) && !is_null($redirectUrl)) {
                return redirect()->away($redirectUrl);
            } else {
                return redirect()->route('securecheckout.payment.request.completed', [
                    'p_charge' => $charge->getKey()
                ]);
            }
        } else {
          return $this->redirectError($charge);
        }
      } else {
        if ($request->query->get('redirect_status') === 'failed') {
          return $this->redirectError($charge);
        } else {
          // We can get this redirect too fast, so wait order completion
          return Response::view('shop.checkout.processing', [
            'business' => $business,
            'charge_id' => $charge->id,
            'timeout' => 60
          ]);
        }
      }
    }

    function redirectError ($charge, $message = 'Payment failed') {
      $params = [ 'message' => 'Payment failed' ];

      $redirectUrl = $charge->paymentRequest->getRedirectUrl([
        'status' => 'canceled',
        'reference' => $charge->paymentRequest->id,
      ]);

      if ($redirectUrl) {
        $params['referer'] = $redirectUrl;
      }
      
      return Response::view('shop.checkout.simpleerror', $params);  
    }
}
