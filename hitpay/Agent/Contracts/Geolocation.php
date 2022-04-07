<?php

namespace HitPay\Agent\Contracts;

/**
 * @property-read int|string $ip_from
 * @property-read int|string $ip_to
 * @property-read string|null $country_code
 * @property-read string|null $country_name
 * @property-read string|null $region_name
 * @property-read string|null $city_name
 * @property-read float|null $latitude
 * @property-read float|null $longitude
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
interface Geolocation
{
    /**
     * Find a geolocation by an Internet Protocol dotted address.
     *
     * @param string $address
     *
     * @return static|null
     */
    public static function findByAddress(string $address);
}
