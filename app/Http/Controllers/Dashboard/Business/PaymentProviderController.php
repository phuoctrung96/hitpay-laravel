<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Http\Controllers\Controller;
use App\Manager\BusinessManagerInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;

class PaymentProviderController extends Controller
{
    /**
     * PayNowController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function home(
        Request $request,
        Business $business,
        $tab = 'paynow',
        BusinessManagerInterface $businessManager
    )
    {
        Gate::inspect('view', $business)->authorize();

        $basicPaymentProvider = $businessManager->getBasicAvailablePaymentProviderWithoutCheck($business);

        $requestProviders = $basicPaymentProvider->pluck('data.code')->toArray();

        $disabled_providers = [];

        if ($business->allowGrabPay()) {
          $requestProviders[] = PaymentProviderEnum::GRABPAY;
        } else {
          $disabled_providers[] = PaymentProviderEnum::GRABPAY;
        }

        if ($business->allowZip()) {
          $requestProviders[] = PaymentProviderEnum::ZIP;
        } else {
          $disabled_providers[] = PaymentProviderEnum::ZIP;
        }

        if ($business->allowShopee()) {
          $requestProviders[] = PaymentProviderEnum::SHOPEE_PAY;
        } else {
          $disabled_providers[] = PaymentProviderEnum::SHOPEE_PAY;
        }

        // Do not include "deleted" payment providers like Stripe or Shopee
        // in this case time added to payment_provider field
        $providers = $business->paymentProviders()
          ->whereNotNull('payment_provider_account_id')
          ->whereIn('payment_provider', $requestProviders)->get();

        $bankList = $businessManager->getAvailableBanks($business);

        if ($tab !== 'paynow' && $tab !== 'stripe') {
          $tab = 'paynow';
        }

        $business_verified = $business->businessVerified();

        return Response::view('dashboard.business.payment-providers.index', compact('business', 'providers', 'disabled_providers', 'bankList', 'tab', 'business_verified'));
    }
}
