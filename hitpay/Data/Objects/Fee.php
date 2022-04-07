<?php

namespace HitPay\Data\Objects;

use HitPay\Data\Objects\Fee\Breakdown;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

/**
 * @property-read int $exchange_rate
 * @property-read int $discount_fee_rate
 * @property-read int $home_currency_total_amount,
 * @property-read int $home_currency_fixed_fee_amount,
 * @property-read int $home_currency_discount_fee_amount,
 * @property-read int $settlement_currency_total_amount,
 * @property-read int $settlement_currency_fixed_fee_amount,
 * @property-read int $settlement_currency_discount_fee_amount,
 * @property-read array|\HitPay\Data\Objects\Fee\Breakdown[] $breakdown
 * @property-read \HitPay\Data\Objects\Fee\Breakdown $breakdown_home_currency
 * @property-read \HitPay\Data\Objects\Fee\Breakdown $breakdown_settlement_currency
 * @property-read bool $via_platform
 */
class Fee extends Base
{
    /**
     * Fee Constructor
     *
     * @param  string  $homeCurrency
     * @param  string  $settlementCurrency
     * @param  float  $exchangeRate
     * @param  float  $discountFeeRate
     * @param  \HitPay\Data\Objects\Fee\Breakdown  $homeCurrencyBreakdown
     * @param  \HitPay\Data\Objects\Fee\Breakdown  $settlementCurrencyBreakdown
     * @param  bool  $viaPlatform
     */
    public function __construct(
        string $homeCurrency,
        string $settlementCurrency,
        float $exchangeRate,
        float $discountFeeRate,
        Breakdown $homeCurrencyBreakdown,
        Breakdown $settlementCurrencyBreakdown,
        bool $viaPlatform = false
    ) {
        $this->data['home_currency'] = $homeCurrency;
        $this->data['settlement_currency'] = $settlementCurrency;
        $this->data['exchange_rate'] = $exchangeRate;
        $this->data['discount_fee_rate'] = $discountFeeRate;
        $this->data['breakdown'] = [
            'home_currency' => $homeCurrencyBreakdown,
            'settlement_currency' => $settlementCurrencyBreakdown,
        ];
        $this->data['via_platform'] = $viaPlatform;
    }

    /**
     * @inheritdoc
     */
    public function __get($key)
    {
        if (key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        foreach ([ 'home_currency', 'settlement_currency' ] as $index) {
            if (Str::startsWith($key, "{$index}_")) {
                $key = Str::replaceFirst("{$index}_", '', $key);

                return $this->data['breakdown'][$index]->{$key};
            }
        }

        if (Str::startsWith($key, 'breakdown_')) {
            $key = Str::replaceFirst('breakdown_', '', $key);

            return $this->data['breakdown'][$key] ?? null;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function toArray() : array
    {
        return $this->convert($this->data);
    }

    protected function convert(array $array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->convert($value);
            } else {
                $data[$key] = $value instanceof Arrayable ? $value->toArray() : $value;
            }
        }

        return $data;
    }
}
