<?php

namespace HitPay\Data\Countries;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

abstract class Country
{
    const skip_verification = false;

    private static array $rawData = [];

    private static array $processedData = [];

    const USE_LOCAL_PHONE_NUMBER_ONLY = false;

    /**
     * Get the raw data of the country.
     *
     * @param  string|null  $key
     * @param  null  $default
     *
     * @return mixed
     */
    public static function getRawData(?string $key = null, $default = null)
    {
        $class = static::getClass();

        if (!key_exists($class, self::$rawData)) {
            $data = require base_path("hitpay/Data/Countries/files/{$class}.php");

            if (!App::isProduction()) {
                if (file_exists(base_path("hitpay/Data/Countries/files_test/{$class}.php"))) {
                    $testData = require base_path("hitpay/Data/Countries/files_test/{$class}.php");

                    unset($testData['id']);

                    $data = static::mergeDataRecursively($data, $testData);
                }
            }

            self::$rawData[$class] = $data;
        }

        if (is_null($key)) {
            return self::$rawData[$class];
        }

        return self::$rawData[$class][$key] ?? $default;
    }

    /**
     * Get the currency collection for the country.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function currencies() : Collection
    {
        $class = static::getClass();
        $dataKey = 'currencies';

        if (!isset(static::$processedData[$class][$dataKey])) {
            static::$processedData[$class][$dataKey] = Collection::make(static::getRawData($dataKey, []));
        }

        return static::$processedData[$class][$dataKey];
    }

    /**
     * Get default currency for the country.
     */
    public static function default_currency() : string
    {
        $class = static::getClass();
        $dataKey = 'default_currency';

        if (!isset(static::$processedData[$class][$dataKey])) {
            static::$processedData[$class][$dataKey] = static::getRawData($dataKey, []);
        }

        return static::$processedData[$class][$dataKey];
    }

    /**
     * Get the bank collection for the country.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function banks() : Collection
    {
        $class = static::getClass();
        $dataKey = 'banks';

        if (!isset(static::$processedData[$class][$dataKey])) {
            $banks = Collection::make();

            foreach (static::getRawData($dataKey, []) as $bankData) {
                $banks->push(( new Objects\Bank(strtolower($class)) )->setData($bankData));
            }

            static::$processedData[$class][$dataKey] = $banks->sortBy('name')->values();
        }

        return static::$processedData[$class][$dataKey];
    }

    /**
     * Get the payment provider collection for the country.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function paymentProviders() : Collection
    {
        $class = static::getClass();
        $dataKey = 'payment_providers';

        if (!isset(static::$processedData[$class][$dataKey])) {
            $paymentProviders = Collection::make();

            foreach (static::getRawData($dataKey, []) as $paymentProviderData) {
                $paymentProvider = new Objects\PaymentProvider(strtolower($class));

                $paymentProvider->setData($paymentProviderData);

                $paymentProviders->push($paymentProvider);
            }

            static::$processedData[$class][$dataKey] = $paymentProviders->sortBy('name')->values();
        }

        return static::$processedData[$class][$dataKey];
    }

    /**
     * Get class name for cache and file retrieval.
     *
     * @return string
     */
    public static function getClass() : string
    {
        return Arr::last(explode('\\', static::class));
    }

    /**
     * A helper to merge data recursively for non-production environment.
     *
     * @param  array  $array1
     * @param  array  $array2
     * @param  int  $level
     *
     * @return array
     */
    private static function mergeDataRecursively(array &$array1, array &$array2, int &$level = 0) : array
    {
        $level++;

        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                if (static::isJustMergeData($level, $key)) {
                    $merged[$key] = array_merge($merged[$key], $value);
                } else {
                    $merged[$key] = static::mergeDataRecursively($merged[$key], $value, $level);
                }
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Determine whether the data is a "just merge" data for non-production environment.
     *
     * @param  int  $level
     * @param  string  $key
     *
     * @return bool
     */
    private static function isJustMergeData(int $level, string $key) : bool
    {
        $keys = Collection::make([
            [ 'level' => 1, 'key' => 'banks' ],
        ]);

        return $keys->where('level', $level)->where('key', $key)->first() !== null;
    }
}
