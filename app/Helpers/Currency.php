<?php

namespace App\Helpers;

use App\Enumerations\CurrencyCode;

/**
 * Class Currency
 * @package App\Helpers
 */
class Currency
{
    /**
     * @param $currency
     * @return bool
     */
    public static function isZeroDecimal($currency)
    {
        $currency = $currency ? strtolower($currency) : '';

        return in_array($currency, CurrencyCode::ZERO_DECIMAL_CURRENCIES);
    }

    /**
     * @param $amount
     * @param $currency
     * @return float|int|mixed|string|null
     */
    public static function getReadableAmount($amount, $currency)
    {
        if (self::isZeroDecimal($currency)) {
            $amount = number_format(getReadableAmountByCurrency($currency, $amount));
        } else {
            $amount = number_format(getReadableAmountByCurrency($currency, $amount), 2);
        }

        return $amount;
    }

    /**
     * @param $currency
     * @return false|string
     */
    public static function getCurrencySymbol($currency)
    {
        $currency = $currency ? strtolower($currency) : '';

        if (in_array($currency, array_keys(CurrencyCode::CURRENCY_SYMBOLS))) {
            return CurrencyCode::CURRENCY_SYMBOLS[$currency];
        }

        return false;
    }
}
