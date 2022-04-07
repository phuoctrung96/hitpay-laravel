<?php

namespace App\Http\Controllers\Admin;

use App\Business;
use App\Business\PaymentProvider;
use App\Business\PaymentProviderRate;
use App\Enumerations\Business\Channel;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Http\Controllers\Controller;
use App\Services\Rates\CustomRatesService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BusinessRateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    public function create(Business $business, string $paymentProvider)
    {
        $paymentProvider = $business->paymentProviders()
            ->with('rates')
            ->where('payment_provider', $this->validateProvider($paymentProvider, $business))
            ->firstOrFail();

        return Response::view('admin.business.custom-rate', compact('business', 'paymentProvider'));
    }

    public function store(Request $request, CustomRatesService $customRatesService, Business $business, string $paymentProvider)
    {
        $paymentProvider = $business->paymentProviders()
            ->with('rates')
            ->where('payment_provider', $this->validateProvider($paymentProvider, $business))
            ->firstOrFail();

        $customRatesService->setCustomRate($paymentProvider, $request->input('method'), $request->input('channel'), $request);

        return Response::redirectToRoute('admin.business.show', [
            'business_id' => $business->getKey(),
        ]);
    }

    public function destroy(Business $business, string $id)
    {
        $rates = $business->rates()->find($id);

        if ($rates) {
            $rates->delete();
        }

        return Response::redirectToRoute('admin.business.show', [
            'business_id' => $business->getKey(),
        ]);
    }

    private function validateProvider ($paymentProvider, $business) {
      $noTranslate = [
        PaymentProviderEnum::GRABPAY,
        PaymentProviderEnum::SHOPEE_PAY,
        PaymentProviderEnum::ZIP
      ];

      if ($paymentProvider === 'stripe') {
          return $business->payment_provider;
      } elseif ($paymentProvider === 'paynow') {
          return PaymentProviderEnum::DBS_SINGAPORE;
      } elseif (in_array($paymentProvider, $noTranslate)) {
          return $paymentProvider;
      } else {
          App::abort(404);
      }      
    }
}
