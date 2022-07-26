<?php

namespace App\Manager;

use App\Actions\Exceptions\BadRequest;
use App\Business;
use App\Business\GatewayProvider;
use App\Business\PaymentProvider;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\CountryCode;
use App\Enumerations\OnboardingStatus;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Providers\AppServiceProvider;
use Exception;
use HitPay\Data\Countries;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Account;
use Stripe\Exception\InvalidRequestException;
use Stripe\Stripe;
use Stripe\StripeObject;
use Stripe\Terminal\ConnectionToken;
use Stripe\Terminal\Location;

class BusinessManager extends AbstractManager implements ManagerInterface, BusinessManagerInterface
{
    public function getClass()
    {
        return Business::class;
    }

    /**
     * Get business Stripe terminal location, create one if it doesn't exist.
     *
     * @param  \App\Business  $business
     * @param  \App\Business\PaymentProvider  $businessPaymentProvider
     *
     * @return \App\Business\StripeTerminalLocation
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function getBusinessStripeTerminalLocations(
        Business $business,
        PaymentProvider $businessPaymentProvider
    ) : Business\StripeTerminalLocation
    {
        // TODO
        //   ---->>>>
        //   Assuming no exception, we will clean up this, move the code to somewhere, and do more validation later.
        //
        $businessPaymentProviderConfiguration = $businessPaymentProvider->getConfiguration();

        if (in_array($businessPaymentProviderConfiguration->official_code, [
            PaymentProviderEnum::STRIPE_SINGAPORE,
            PaymentProviderEnum::STRIPE_US,
        ])) {
            throw new Exception('The Stripe terminal location is available for Singapore and United States only');
        }

        $country = $businessPaymentProviderConfiguration->getCountry();

        Stripe::setApiKey(Config::get("services.stripe.{$country}.secret"));

        foreach ($business->stripeTerminalLocations as $businessStripeTerminalLocation) {
            try {
                $stripeLocation = Location::retrieve($businessStripeTerminalLocation->stripe_terminal_location_id, [
                    'stripe_version' => AppServiceProvider::STRIPE_VERSION
                ]);
            } catch (InvalidRequestException $exception) {
                // TODO
                //   ---->>>>
                //   If not found, shall we delete it?
                //
                continue;
            }

            if ($stripeLocation instanceof Location) {
                return $businessStripeTerminalLocation;
            }
        }

        $address = [ 'country' => $business->country ];

        if ($businessPaymentProviderConfiguration->code === PaymentProviderEnum::STRIPE_US) {
            $stripeAccount = Account::retrieve($businessPaymentProvider->payment_provider_account_id, [
                'stripe_version' => AppServiceProvider::STRIPE_VERSION,
            ]);

            if ($stripeAccount instanceof Account) {
                switch (false) {
                    case $stripeAccount->company instanceof StripeObject:
                    case $stripeAccount->company->address instanceof StripeObject:
                        $message = "Unable to create Stripe terminal location for business {$business->getKey()}, the address of the company in Stripe account can't be retrieved.";

                        Log::error($message);

                        throw new BadRequest($message);
                    default:
                        $address['line1'] = $stripeAccount->company->address->line1;
                        $address['line2'] = $stripeAccount->company->address->line2;
                        $address['city'] = $stripeAccount->company->address->city;
                        $address['state'] = $stripeAccount->company->address->state;
                        $address['postal_code'] = $stripeAccount->company->address->postal_code;
                }
            }
        }

        try {
            $stripeLocation = Location::create([
                'display_name' => $business->getName(),
                'address' => $address,
            ], [ 'stripe_version' => AppServiceProvider::STRIPE_VERSION ]);
        } catch (InvalidRequestException $exception) {
            Log::error(
                "Error when creating Stripe terminal location for business : {$business->getKey()}. Error from Stripe: {$exception->getMessage()}"
            );

            throw new BadRequest($exception->getMessage());
        }

        try {
            $businessStripeTerminalLocation = DB::transaction(
                function (Connection $connection) use ($business, $stripeLocation, $businessPaymentProvider) {
                    return $business->stripeTerminalLocations()->create([
                        'name' => $stripeLocation->display_name,
                        'payment_provider' => $businessPaymentProvider->payment_provider,
                        'stripe_terminal_location_id' => $stripeLocation->id,
                        'data' => $stripeLocation->toArray(),
                    ]);
                },
                3
            );
        } catch (Exception $exception) {
            $stripeLocation->delete();

            throw $exception;
        }

        return $businessStripeTerminalLocation;
    }

    /**
     * Create a Stripe connection token for terminal.
     *
     * @param  \App\Business  $business
     *
     * @return string|null
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function createStripeConnectionToken(Business $business) : ?string
    {
        $businessPaymentProvider = $business
            ->paymentProviders()
            ->where('payment_provider', $business->payment_provider)
            ->first();

        if (!$businessPaymentProvider instanceof PaymentProvider) {
            return null;
        }

        try {
            $location = $this->getBusinessStripeTerminalLocations($business, $businessPaymentProvider);
        } catch (BadRequest $exception) {
            return null;
        }
        $country = $businessPaymentProvider->getConfiguration()->getCountry();

        Stripe::setApiKey(Config::get("services.stripe.{$country}.secret"));

        $token = ConnectionToken::create([
            'location' => $location->stripe_terminal_location_id,
        ], [ 'stripe_version' => AppServiceProvider::STRIPE_VERSION ]);

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

            if ($this->allowStripeAlipay($business, $currency)) {
              $paymentMethods[PaymentMethodType::ALIPAY]   = 'AliPay';
            }

            if ($stripeProvider->payment_provider === PaymentProviderEnum::STRIPE_MALAYSIA && (!$currency || strtolower($currency) === 'myr')) {
              $paymentMethods[PaymentMethodType::FPX] = 'FPX';
            }

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

        if ($provider == 'shopify')
            $provider = PluginProvider::APISHOPIFY;

        $paymentMethods = $business->getProviderMethods($provider);

        if (!is_null($currency)) {
            $cur = strtolower($currency);

            foreach ($paymentMethods as $key => $paymentMethod) {
              if ($cur !== 'sgd') {
                if ($paymentMethod == PaymentMethodType::PAYNOW) {
                  unset($paymentMethods[$key]);
                }

                if ($paymentMethod == PaymentMethodType::GRABPAY_DIRECT || $paymentMethod == PaymentMethodType::GRABPAY_PAYLATER) {
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

              if (($business->currency === 'myr' && $cur !== 'myr') || ($business->currency === 'sgd' && $cur !== 'sgd')) {
                if ($paymentMethod !== PaymentMethodType::CARD) {
                  unset($paymentMethods[$key]);
                }
              }

              if ($paymentMethod === PaymentMethodType::ALIPAY && !$this->allowStripeAlipay($business, $cur)) {
                unset($paymentMethods[$key]);
              }
            }

            $paymentMethods = array_values($paymentMethods);
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

        if (!$this->allowStripeAlipay($business, $business->currency)) {
          $paymentMethods = array_values(array_filter($paymentMethods, function ($value) {
            return $value !== PaymentMethodType::ALIPAY;
          }));
        }

        return $paymentMethods;
    }

    public function getBusinessPaymentRequestMethods(Business $business, array $methods, $currency = 'sgd')
    {
        $paymentMethods     = [];

        [ $payNowProvider, $stripeProvider, $shopeeProvider, $hoolahProvider, $grabpayProvider, $zipProvider ] = $this->getProviders($business);

        $myMYR = $business->country === 'my' && strtolower($currency) === 'myr';

        foreach ($methods as $method) {
            switch ($method) {
                case PaymentMethodType::ALIPAY:
                case PaymentMethodType::GRABPAY:
                case PaymentMethodType::WECHAT:
                case PaymentMethodType::CARD:
                case PaymentMethodType::FPX:
                    if ($stripeProvider instanceof PaymentProvider) {
                        if ($method === PaymentMethodType::CARD) {
                            $paymentMethods[] = PaymentMethodType::CARD;
                        }

                        if ($method === PaymentMethodType::WECHAT) {
                            $paymentMethods[] = PaymentMethodType::WECHAT;
                        }

                        // AliPay
                        // MY - only MYR currency
                        // Other - all currencies
                        if ($method === PaymentMethodType::ALIPAY && $this->allowStripeAlipay($business, strtolower($currency))) {
                            $paymentMethods[] = PaymentMethodType::ALIPAY;
                        }

                        // FPX only for MY
                        if ($method === PaymentMethodType::FPX && $myMYR) {
                          $paymentMethods[] = PaymentMethodType::FPX;
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
            PaymentProviderEnum::STRIPE_MALAYSIA,
            PaymentProviderEnum::STRIPE_US
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

    function allowStripeGrabPay(Business $business, $stripeProvider, $grabpayProvider)
    {
        if ($business->country !== 'my') {
            return false;
        }

        return !( $grabpayProvider instanceof PaymentProvider );
    }

    function allowStripeAlipay(Business $business, $currency)
    {
        switch ($business->country) {
          case 'sg':
            return true;

          case 'my':
            return !config('services.stripe.my.disable_alipay') && (empty($currency) || strtolower($currency) === 'myr');

          default:
            return false;
        }
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
     * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     * @throws \Exception
     */
    public function getStripePublishableKey(Business $business)
    {
        if (in_array($business->country, CountryCode::listConstants())) {
            if ($business->country === 'sg') {
                return config('services.stripe.sg.key');
            }

            if ($business->country === 'my') {
                return config('services.stripe.my.key');
            }

            return config('services.stripe.us.key');
        } else {
            throw new Exception("stripe not yet supported with business " . $business->getKey());
        }
    }
}
