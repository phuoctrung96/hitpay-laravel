<?php

namespace HitPay\Stripe;

use App\Enumerations\CountryCode;
use App\Enumerations\CurrencyCode;
use App\Enumerations\PaymentProvider;
use App\Exceptions\HitPayLogicException;
use HitPay\Data\Countries;
use Illuminate\Support\Facades\Config;
use Stripe\Stripe;

abstract class Core
{
    /**
     * The configuration for set payment provider.
     *
     * @var array
     */
    public $configurations;

    /**
     * The payment provider code.
     *
     * @var string
     */
    public $paymentProvider;

    /**
     * The available countries.
     */
    public static function getCountries(): array
    {
        $countries = [];

        foreach (Countries::all() as $country_code) {
            if (CountryCode::MALAYSIA === $country_code) {
                $countries[$country_code] = [
                    'code' => CountryCode::MALAYSIA,
                    'payment_provider' => PaymentProvider::STRIPE_MALAYSIA,
                    'currency' => CurrencyCode::MYR,
                    'payment_methods' => [
                        'card',
                    ],
                ];
            } elseif (CountryCode::SINGAPORE === $country_code) {
                $countries[$country_code] = [
                    'code' => CountryCode::SINGAPORE,
                    'payment_provider' => PaymentProvider::STRIPE_SINGAPORE,
                    'currency' => CurrencyCode::SGD,
                    'payment_methods' => [
                        'card',
                        'alipay',
                        'wechat',
                    ],
                ];
            } else {
                $country = Countries::get($country_code);

                $countries[$country_code] = [
                    'code' => $country_code,
                    'payment_provider' => PaymentProvider::STRIPE_US,
                    'currency' => $country::default_currency(),
                    'payment_methods' => [
                        'card',
                    ],
                ];
            }
        }

        return $countries;
    }

    /**
     * Core constructor.
     *
     * @param string $paymentProvider
     * @param string|null $accountId
     * @param bool $setClientId
     *
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function __construct(string $paymentProvider, string $accountId = null, bool $setClientId = false)
    {
        if ($paymentProvider === PaymentProvider::STRIPE_SINGAPORE) {
            $this->configurations = Config::get('services.stripe.sg');
        } elseif ($paymentProvider === PaymentProvider::STRIPE_MALAYSIA) {
            $this->configurations = Config::get('services.stripe.my');
        } elseif ($paymentProvider === PaymentProvider::STRIPE_US) {
            $this->configurations = Config::get('services.stripe.us');
        } else {
            throw new HitPayLogicException("The payment provider '$paymentProvider' is not available.");
        }

        $this->paymentProvider = $paymentProvider;

        Stripe::setApiKey($this->configurations['secret']);

        if (!is_null($accountId)) {
            Stripe::setAccountId($accountId);
        } elseif ($setClientId) {
            Stripe::setClientId($this->configurations['client_id']);
        }

        return $this;
    }

    /**
     * Create new instance by payment provider.
     *
     * @param string $paymentProvider
     * @param string|null $accountId
     *
     * @return static
     * @throws \App\Exceptions\HitPayLogicException
     */
    public static function new(string $paymentProvider, string $accountId = null) : self
    {
        return new static($paymentProvider, $accountId);
    }

    /**
     * Create new instance by country.
     *
     * @param string $country
     * @param string|null $accountId
     *
     * @return static
     * @throws \App\Exceptions\HitPayLogicException
     */
    public static function newByCountry(string $country, string $accountId = null) : self
    {
        $paymentProvider = static::getStripePlatformByCountry($country);

        return new static($paymentProvider, $accountId);
    }

    /**
     * Create new instance with client ID.
     *
     * @param string $paymentProvider
     *
     * @return static
     * @throws \App\Exceptions\HitPayLogicException
     */
    public static function newWithClientId(string $paymentProvider) : self
    {
        return new static($paymentProvider, null, true);
    }

    /**
     * Get Stripe platform by country.
     *
     * @param string $country
     *
     * @return string
     */
    public static function getStripePlatformByCountry(string $country) : string
    {
        return static::getCountries()[$country]['payment_provider'];
    }
}
