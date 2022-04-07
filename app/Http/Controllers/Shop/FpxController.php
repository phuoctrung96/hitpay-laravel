<?php

namespace App\Http\Controllers\Shop;

use App\Business\PaymentIntent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use App\Enumerations\Business\ChargeStatus;

class FpxController extends Controller
{
    public function handleRedirect (Request $request, string $paymentIntentId) {
      $paymentIntent = PaymentIntent::findOrFail($paymentIntentId);
      $charge = $paymentIntent->charge;
      $business = $paymentIntent->business()->first();

      if ($request->query->has('noWait')) {
        if ($charge->status === ChargeStatus::SUCCEEDED) { 
          if ($charge->paymentRequest->redirect_url) {
            return redirect()->away($charge->paymentRequest->redirect_url . '?reference=' . $charge->paymentRequest->id . '&status=completed');
          } else {
            return redirect()->route('securecheckout.payment.request.completed', ['p_charge' => $charge->getKey()]);
          }
        } else {
          return Response::view('shop.checkout.simpleerror', ['message' => 'Payment failed']);
        } 
      } else {
        if ($request->query->get('redirect_status') === 'failed') {
          return Response::view('shop.checkout.simpleerror', ['message' => 'Payment failed']);
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
}
