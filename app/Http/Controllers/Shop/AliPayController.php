<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AliPayController extends Controller
{
    public function handleRedirect (Request $request) {
      if ($request->get('redirect_status') === 'succeeded') {
        return redirect(route('securecheckout.payment.request.completed', $request->get('b_charge')));
      } else {
        return Response::view('shop.checkout.simpleerror', ['message' => 'Payment failed']);
      }      
    }
}
