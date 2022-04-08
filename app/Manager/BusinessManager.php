<?php

namespace App\Manager;

use App\Business;
use App\Business\GatewayProvider;
use App\Business\PaymentProvider;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\OnboardingStatus;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use HitPay\Data\Countries;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Stripe\Stripe;
use Stripe\Terminal\ConnectionToken;

class BusinessManager extends AbstractManager implements ManagerInterface, BusinessManagerInterface
{
    public function getClass()
    {
        return Business::class;
    }

    public function createStripeConnectionToken(Business $business)
    {
        $provider   = $business->paymentProviders()->where('payment_provider', $business->payment_provider)->first();
        $locations  = $business->stripeTerminalLocations;
        $location   = $locations->first();

        // Set your secret key. Remember to switch to your live secret key in production!
        // See your keys here: https://dashboard.stripe.com/account/apikeys
        Stripe::setApiKey(Config::get('services.stripe.sg.secret'));

        // In a new endpoint on your server, create a ConnectionToken and return the
        // `secret` to your app. The SDK needs the `secret` to connect to a reader.
        $token      = ConnectionToken::create([
            'location' => $location->stripe_terminal_location_id,
        ]);

        return $token->secret;
    }

    public function getByBusinessAvailablePaymentMethods(Business $business, $currency = null, $all = false)
    {
        $paymentMethods = [];

        [ $payNowProvider, $stripeProvider, $shopeeProvider, $hoolahProvider, $grabpayProvider, $zipProvider ] = $this->getProviders($business);

        if ($payNowProvider instanceof PaymentProvider && (empty($currency) || strtolower($currency) === 'sgd')) {
            $paymentMethods[PaymentMethodType::PAYNOW]   = 'PayNow';
        }

        if ($stripeProvider instanceof PaymentProvider) {
            $paymentMethods[PaymentMethodType::CARD]     = 'Visa, Mastercard and American Express (Including Apple Pay and Google Pay) ';

            if ($stripeProvider->payment_provider === PaymentProviderEnum::STRIPE_SINGAPORE) {
                $paymentMethods[PaymentMethodType::WECHAT] = 'WeChatPay';
            }

            $paymentMethods[PaymentMethodType::ALIPAY]   = 'AliPay';

            if ($all && ( !isset($currency) || in_array(strtolower($currency), [ 'sgd', 'myr' ]) )
                && $this->allowStripeGrabPay($business, $stripeProvider, $grabpayProvider)) {
                // Stripe GrabPay
                $paymentMethods[PaymentMethodType::GRABPAY] = 'GrabPay';
            }
        }

        if ($all && (!isset($currency) || strtolower($currency) === 'sgd')) {
          if ($grabpayProvider instanceof PaymentProvider) {
            $paymentMethods[PaymentMethodType::GRABPAY_DIRECT]  = 'GrabPay Direct';
            $paymentMethods[PaymentMethodType::GRABPAY_PAYLATER]  = 'GrabPay PayLater';
          }

          if ($shopeeProvider instanceof PaymentProvider) {
            $paymentMethods[PaymentMethodType::SHOPEE]  = 'Shopee Pay';
          }

          if ($hoolahProvider instanceof PaymentProvider) {
            // !!! Hoolah
            //$paymentMethods[PaymentMethodType::HOOLAH]  = 'Hoolah';
          }

          if ($zipProvider instanceof PaymentProvider) {
            $paymentMethods[PaymentMethodType::ZIP]  = 'Zip';
          }
        }

        return $paymentMethods;
    }

    /**
     * @param Business $business
     * @param $provider
     * @param null $currency
     * @return array
     */
    public function getBusinessProviderPaymentMethods(Business $business, $provider, $currency = null)
    {

        if ($provider == 'api_custom' || ($provider == '')) {
            $provider = PluginProvider::getProviderByChanel(PluginProvider::CUSTOM);
        }
        $provider       = $business->gatewayProviders()->where('name', $provider)->first();
        $paymentMethods = [];

        if ($provider instanceof GatewayProvider) {
            foreach ($provider->array_methods as $method) {
                $paymentMethods[] = $method;
            }
        }

        if (!is_null($currency)) {
            if (strtolower($currency) != 'sgd') {
                foreach ($paymentMethods as $key => $paymentMethod) {
                    if ($paymentMethod == PaymentMethodType::PAYNOW) {
                      unset($paymentMethods[$key]);
                    }

                    if ($paymentMethod == PaymentMethodType::GRABPAY || $paymentMethod == PaymentMethodType::GRABPAY_DIRECT || $paymentMethod == PaymentMethodType::GRABPAY_PAYLATER) {
                      unset($paymentMethods[$key]);
                    }

                    if ($paymentMethod == PaymentMethodType::SHOPEE) {
                      unset($paymentMethods[$key]);
                    }

                    if ($paymentMethod == PaymentMethodType::HOOLAH) {
                      unset($paymentMethods[$key]);
                    }

                    if ($paymentMethod == PaymentMethodType::ZIP) {
                      unset($paymentMethods[$key]);
                    }
                }

                $paymentMethods = array_values($paymentMethods);
            }
        }

        return $paymentMethods;
    }

    public function getBusinessPaymentMethods(Business $business, $provider)
    {
        $provider       = $business->gatewayProviders()->where('name', $provider)->first();
        $paymentMethods = [];

        if ($provider instanceof GatewayProvider) {
            foreach ($provider->array_methods as $method) {
                $paymentMethods[] = $method;
            }
        } else {
            $paymentMethods = $this->getDefaultBusinessPaymentMethods($business, $provider);
        }

        // filter out GrabPay if it is disabled for current business but was enablied earlier
        if (!$business->allowGrabPay()) {
          $paymentMethods = array_values(array_filter($paymentMethods, function ($value) {
            return $value !== PaymentMethodType::GRABPAY_DIRECT && $value !== PaymentMethodType::GRABPAY_PAYLATER;
          }));
        }

        // filter out Zip  if it is disabled for current business but was enablied earlier
        if (!$business->allowZip()) {
          $paymentMethods = array_values(array_filter($paymentMethods, function ($value) {
            return $value !== PaymentMethodType::ZIP;
          }));
        }

        // filter out GrabPay if it is disabled for current business but was enablied earlier
        if (!$business->allowShopee()) {
          $paymentMethods = array_values(array_filter($paymentMethods, function ($value) {
            return $value !== PaymentMethodType::SHOPEE;
          }));
        }

        return $paymentMethods;
    }

    public function getBusinessPaymentRequestMethods(Business $business, array $methods)
    {
        $paymentMethods     = [];

        [ $payNowProvider, $stripeProvider, $shopeeProvider, $hoolahProvider, $grabpayProvider, $zipProvider ] = $this->getProviders($business);

        foreach ($methods as $method) {
            switch ($method) {
                case PaymentMethodType::ALIPAY:
                case PaymentMethodType::GRABPAY:
                case PaymentMethodType::WECHAT:
                case PaymentMethodType::CARD:
                    if ($stripeProvider instanceof PaymentProvider) {
                        if ($method === PaymentMethodType::CARD) {
                            $paymentMethods[] = PaymentMethodType::CARD;
                        }

                        if ($method === PaymentMethodType::WECHAT) {
                            $paymentMethods[] = PaymentMethodType::WECHAT;
                        }

                        if ($method === PaymentMethodType::ALIPAY) {
                            $paymentMethods[] = PaymentMethodType::ALIPAY;
                        }

                        // only if there is no GrabPay direct
                        if ($method === PaymentMethodType::GRABPAY && $this->allowStripeGrabPay($business, $stripeProvider, $grabpayProvider)) {
                          $paymentMethods[] = PaymentMethodType::GRABPAY;
                        }
                    }

                    break;

                case PaymentMethodType::PAYNOW:
                    if ($payNowProvider instanceof PaymentProvider) {
                        $paymentMethods[] = PaymentMethodType::PAYNOW;
                    }

                    break;

                case PaymentMethodType::SHOPEE:
                    if ($shopeeProvider instanceof PaymentProvider) {
                      $paymentMethods[] = PaymentMethodType::SHOPEE;
                    }

                    break;

                case PaymentMethodType::GRABPAY_DIRECT:
                case PaymentMethodType::GRABPAY_PAYLATER:
                    if ($grabpayProvider instanceof PaymentProvider) {
                      $paymentMethods[] = $method;
                    }

                    break;

                case PaymentMethodType::HOOLAH:
                  if ($hoolahProvider instanceof PaymentProvider) {
                    $paymentMethods[] = PaymentMethodType::HOOLAH;
                  }

                break;

                case PaymentMethodType::ZIP:
                  if ($zipProvider instanceof PaymentProvider) {
                    $paymentMethods[] = PaymentMethodType::ZIP;
                  }

                break;
            }
        }

        return $paymentMethods;
    }

    public function getDefaultBusinessPaymentMethods(Business $business, $provider)
    {
        $paymentMethods = [];

        [ $payNowProvider, $stripeProvider, $shopeeProvider, $hoolahProvider, $grabpayProvider, $zipProvider ] = $this->getProviders($business);

        if ($payNowProvider instanceof PaymentProvider) {
            $paymentMethods[] = PaymentMethodType::PAYNOW;
        }

        if ($stripeProvider instanceof PaymentProvider) {
            $paymentMethods[] = PaymentMethodType::CARD;

            if ($stripeProvider->payment_provider === PaymentProviderEnum::STRIPE_SINGAPORE) {
                $paymentMethods[PaymentMethodType::WECHAT] = 'WeChatPay';
            }

            $paymentMethods[] = PaymentMethodType::ALIPAY;

            if ($this->allowStripeGrabPay($business, $stripeProvider, $grabpayProvider)) {
              $paymentMethods[] = PaymentMethodType::GRABPAY;
            }
        }

        if ($shopeeProvider instanceof PaymentProvider) {
            $paymentMethods[] = PaymentMethodType::SHOPEE;
        }

        if ($hoolahProvider instanceof PaymentProvider) {
            $paymentMethods[] = PaymentMethodType::HOOLAH;
        }

        if ($grabpayProvider instanceof PaymentProvider) {
          $paymentMethods[] = PaymentMethodType::GRABPAY_DIRECT;
          $paymentMethods[] = PaymentMethodType::GRABPAY_PAYLATER;
        }

        if ($zipProvider instanceof PaymentProvider) {
          $paymentMethods[] = PaymentMethodType::ZIP;
        }

        /*$paymentMethods[] = self::CASH;

        if ($business->stripeTerminals()->count()) {
            $paymentMethods[] = self::CARD_PRESENT;
        }*/

        return $paymentMethods;
    }

    public function getBusinessesConnectedToXero(): Collection
    {
        return Business::query()
            ->whereNotNull('xero_refresh_token')
            ->get();
    }

    /**
     * @param Business $business
     * @return array of payment provider
     */
    function getProviders(Business $business)
    {
        // get basic payment provider with automatic added when register flow
        $country = Countries::get($business->country);

        $basicProviders = $country->paymentProviders();

        $stripeFilter = [
            PaymentProviderEnum::STRIPE_SINGAPORE,
            PaymentProviderEnum::STRIPE_MALAYSIA
        ];

        // get payment provider by request
        $providers = $business->paymentProviders()->get();

        $payNowProvider = $basicProviders->where('code', PaymentProviderEnum::DBS_SINGAPORE)->first();
        if ($payNowProvider) {
            $payNowProvider = $providers->where('payment_provider', PaymentProviderEnum::DBS_SINGAPORE)->first();
        }

        $stripeProvider = $basicProviders->whereIn('code', $stripeFilter)->first();
        if ($stripeProvider) {
            $stripeProvider = $providers->whereIn('payment_provider', $stripeFilter)->first();
        }

        $shopeeProvider = $basicProviders->whereIn('code', PaymentProviderEnum::SHOPEE_PAY)->first();
        if ($shopeeProvider) {
            $shopeeProvider = $business->allowShopee()
                ? $providers->where('payment_provider', PaymentProviderEnum::SHOPEE_PAY)->where('onboarding_status', OnboardingStatus::SUCCESS)->first()
                : null;
        }

        $zipProvider = $basicProviders->whereIn('code', PaymentProviderEnum::ZIP)->first();
        if ($zipProvider) {
            $zipProvider = $business->allowZip()
                ? $providers->where('payment_provider', PaymentProviderEnum::ZIP)->where('onboarding_status', OnboardingStatus::SUCCESS)->first()
                : null;
        }

        $grabpayProvider = $basicProviders->whereIn('code', PaymentProviderEnum::GRABPAY)->first();
        if ($grabpayProvider) {
            $grabpayProvider = $business->allowGrabPay()
                ? $providers->where('payment_provider', PaymentProviderEnum::GRABPAY)->where('onboarding_status', OnboardingStatus::SUCCESS)->first()
                : null;
        }

        $hoolahProvider = $providers->where('payment_provider', PaymentProviderEnum::HOOLAH)->first();

        return [
            $payNowProvider,
            $stripeProvider,
            $shopeeProvider,
            $hoolahProvider,
            $grabpayProvider,
            $zipProvider
        ];
    }

    function allowStripeGrabPay ($business, $stripeProvider, $grabpayProvider) {
      return !($grabpayProvider instanceof PaymentProvider);
    }

    /**
     * @param Business $business
     * @return Collection
     */
    function getBasicAvailablePaymentProviderWithoutCheck(Business $business) : Collection
    {
        // get basic payment provider with automatic added when register flow
        $country = Countries::get($business->country);

        return $country->paymentProviders();
    }

    /**
     * @param Business $business
     * @return Collection
     */
    function getAvailableBanks(Business $business) : Collection
    {
        $country = Countries::get($business->country);

        return $country->banks();
    }

    /**
     * @param Business $business
     * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     * @throws \Exception
     */
    public function getStripePublishableKey(Business $business)
    {
        if ($business->country == 'sg') {
            return config('services.stripe.sg.key');
        }
        else if ($business->country == 'my') {
            return config('services.stripe.my.key');
        }
        else {
            throw new \Exception("stripe not yet supported with business " . $business->getKey());
        }
    }
}
