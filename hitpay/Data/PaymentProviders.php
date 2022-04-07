<?php

namespace HitPay\Data;

use Illuminate\Support\Collection;

class PaymentProviders
{
    protected static Collection $collection;

    /**
     * Get all payment providers of HitPay, from all countries.
     *
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public static function all() : Collection
    {
        if (!isset(static::$collection)) {
            $paymentProviders = [];

            foreach (Countries::all() as $countryCode) {
                $country = Countries::get($countryCode);

                $paymentProviders = array_merge($paymentProviders, $country->paymentProviders()->all());
            }

            static::$collection = Collection::make($paymentProviders);
        }

        return static::$collection;
    }
}
