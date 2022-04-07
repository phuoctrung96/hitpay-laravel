<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Http\Controllers\Controller;
use HitPay\Data\PaymentProviders;
use HitPay\Stripe\CustomAccount\Sync;
use Illuminate\Http;
use Illuminate\Support\Facades;

class PaymentProviderController extends Controller
{
    /**
     * PaymentProviderController Constructor
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the homepage of payment providers.
     *
     * @param  \App\Business  $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showHomePage(Business $business) : Http\Response
    {
        Facades\Gate::inspect('view', $business)->authorize();

        $availablePaymentProviders = $business->paymentProvidersAvailable()->pluck('data.code');
        $paymentProvidersDisabled = [];

        foreach ([
            PaymentProviderEnum::GRABPAY,
            PaymentProviderEnum::ZIP,
        ] as $paymentProviderCode) {
            if (!$business->allowProvider($paymentProviderCode)) {
                $paymentProvidersDisabled[] = $paymentProviderCode;

                $availablePaymentProviders = $availablePaymentProviders->filter(
                    function (string $paymentProvider) use ($paymentProviderCode) {
                        return $paymentProvider !== $paymentProviderCode;
                    }
                );
            }
        }

        if (!$business->allowShopee()) {
            $paymentProvidersDisabled[] = PaymentProviderEnum::SHOPEE_PAY;

            $availablePaymentProviders = $availablePaymentProviders->filter(function (string $paymentProvider) {
                return $paymentProvider !== PaymentProviderEnum::SHOPEE_PAY;
            });
        }

        $availablePaymentProviderCodes = $availablePaymentProviders->toArray();
        $paymentProvidersEnabled = $business->paymentProviders()
            ->whereNotNull('payment_provider_account_id')
            ->whereIn('payment_provider', $availablePaymentProviderCodes)
            ->get();

        $stripeCodes = PaymentProviders::all()->where('official_code', 'stripe')->pluck('code')->toArray();
        $stripePaymentProviders = $paymentProvidersEnabled->whereIn('payment_provider', $stripeCodes);
        $stripePaymentProvidersCount = $stripePaymentProviders->count();

        if ($stripePaymentProvidersCount > 0) {
            /** @var \App\Business\PaymentProvider $stripePaymentProvider */
            $stripePaymentProvider = $stripePaymentProviders->sortByDesc('created_at')->first();

            if ($stripePaymentProvider->payment_provider_account_type === 'custom') {
                $cacheKey = "__refreshed:stripe_account:{$stripePaymentProvider->payment_provider_account_id}";

                if (Facades\App::isLocal()) {
                    Facades\Cache::forget($cacheKey);
                }

                /** @var \App\Business\PaymentProvider $stripePaymentProvider */
                $stripePaymentProvider = Facades\Cache::remember($cacheKey, 1800, function () use ($business) {
                    return Sync::new($business->payment_provider)->setBusiness($business)->handle(null, false);
                });

                // A dirty hack to replace the payment provider in the collection.
                //
                $paymentProvidersEnabled->map(function (
                    Business\PaymentProvider $paymentProvider
                ) use ($stripePaymentProvider) {
                    return $paymentProvider->payment_provider === $stripePaymentProvider->payment_provider
                        ? $stripePaymentProvider
                        : $paymentProvider;
                });
            }

            if ($stripePaymentProvidersCount > 1) {
                Facades\Log::critical(
                    "The business '{$business->getKey()}' has more than 1 Stripe related payment providers (Codes: '{$stripePaymentProviders->join('\', \'', 'payment_provider')}')."
                );
            }
        }

        return Facades\Response::view('dashboard.business.payment-providers.index', [
            'business' => $business,
            'providers' => $paymentProvidersEnabled,
            'disabled_providers' => $paymentProvidersDisabled,
            'bankList' => $business->banksAvailable(), // KIV, this might not in used anymore
            'business_verified' => $business->businessVerified(),
        ]);
    }
}
