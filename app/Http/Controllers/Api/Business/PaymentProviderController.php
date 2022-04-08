<?php

namespace App\Http\Controllers\Api\Business;

use App\Business;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Http\Resources\Business\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Manager\BusinessManagerInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PaymentProviderController extends Controller
{
    /**
     * CustomerController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(
        Request $request,
        Business $business,
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

        $business_verified = $business->businessVerified();

        return [
            'providers' => PaymentProvider::collection($providers),
            'disabled_providers' => $disabled_providers,
            'business_verified' => $business_verified
        ];
    }

    public function banks(
        Request $request,
        Business $business,
        BusinessManagerInterface $businessManager
    )
    {
        Gate::inspect('view', $business)->authorize();

        $banks = $businessManager->getAvailableBanks($business);

        return compact('banks');
    }
}
