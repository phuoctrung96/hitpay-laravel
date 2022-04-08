<?php

namespace HitPay\Data;

use App\Enumerations\CurrencyCode;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades;

class ExchangeRate
{
    protected string $from;

    protected string $to;

    /**
     * Initiate the class.
     *
     * @param  string|null  $fromCode
     * @param  string|null  $toCode
     *
     * @return void
     * @throws \ReflectionException
     */
    public function __construct(string $fromCode = null, string $toCode = null)
    {
        if (is_string($fromCode)) {
            $this->setFrom($fromCode);
        }

        if (is_string($toCode)) {
            $this->setTo($toCode);
        }
    }

    /**
     * Get the "from" currency code.
     *
     * @return string
     */
    public function getFrom() : string
    {
        return $this->from;
    }

    /**
     * Set the "from" currency code.
     *
     * @param  string  $currencyCode
     *
     * @return $this
     * @throws \ReflectionException
     */
    public function setFrom(string $currencyCode) : self
    {
        $this->from = $this->validatedCurrencyCode($currencyCode);

        return $this;
    }

    /**
     * Get the "to" currency code.
     *
     * @return string
     */
    public function getTo() : string
    {
        return $this->to;
    }

    /**
     * Set the "to" currency code.
     *
     * @param  string  $currencyCode
     *
     * @return $this
     * @throws \ReflectionException
     */
    public function setTo(string $currencyCode) : self
    {
        $this->to = $this->validatedCurrencyCode($currencyCode);

        return $this;
    }

    /**
     * Get the already validated currency code, or get bombed.
     *
     * @param  string  $currencyCode
     *
     * @return string
     * @throws \ReflectionException
     * @throws \Exception
     */
    protected function validatedCurrencyCode(string $currencyCode)
    {
        $currencyCode = strtolower($currencyCode);

        if (!CurrencyCode::isValidValue($currencyCode)) {
            throw new Exception("The selected currency code '{$currencyCode}' is invalid.");
        }

        return $currencyCode;
    }

    public function get() : float
    {
        if ($this->from === $this->to) {
            return 1.0;
        }

        $exchangeRates = static::getExchangeRatesFor($this->from);

        if (!array_key_exists($this->to, $exchangeRates)) {
            throw new Exception(
                "Unable to retrieve the exchange rate from currency '{$this->from}' to '{$this->to}'. Seems like it is not supported by the API."
            );
        }

        return $exchangeRates[$this->to];
    }

    /**
     * Helper to initiate the class.
     *
     * @param  string|null  $from
     * @param  string|null  $to
     *
     * @return static
     * @throws \ReflectionException
     */
    public static function new(string $from = null, string $to = null) : self
    {
        return new static($from, $to);
    }

    /**
     * A helper function to refresh the exchange rate for the given currency if necessary.
     *
     * @param  string  $currency
     *
     * @return void
     * @throws \Exception
     */
    public static function refresh(string $currency)
    {
        Facades\Cache::forget("_exchange_rates:{$currency}");

        static::getExchangeRatesFor($currency);
    }

    /**
     * Get the exchange rates for the given currency from cache, or reload if necessary.
     *
     * @param  string  $currency
     *
     * @return array
     * @throws \Exception
     */
    public static function getExchangeRatesFor(string $currency) : array
    {
        $handler = function () use ($currency) {
            $request = ( new Client )->get("https://freecurrencyapi.net/api/v2/latest", [
                'query' => [
                    'apikey' => Facades\Config::get('services.freecurrencyapi.api_key'),
                    'base_currency' => strtoupper($currency),
                ],
            ]);

            $contents = $request->getBody()->getContents();
            $contents = json_decode($contents, true);

            if (
                !(
                    json_last_error() === JSON_ERROR_NONE
                    && is_array($contents)
                    && array_key_exists('data', $contents)
                )
            ) {
                throw new Exception(
                    "Unable to retrieve the exchange rates for currency '{$currency}'. The response data is not a JSON. This will happen if the currency is invalid too. They will return their homepage if currency requested is invalid. We should do a check too."
                );
            }

            $exchangeRates = [];

            foreach ($contents['data'] as $currencyCode => $rate) {
                $exchangeRates[strtolower($currencyCode)] = $rate;
            }

            return $exchangeRates;
        };

        // We make the cache deleted at 6am SGT.
        //
        $expiryDate = Facades\Date::tomorrow()->startOfDay()->addHours(6);

        return Facades\Cache::tags('_exchange_rates')->remember("_exchange_rates:{$currency}", $expiryDate, $handler);
    }
}
