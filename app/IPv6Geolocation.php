<?php

namespace App;

use HitPay\Agent\Contracts\Geolocation;
use Illuminate\Database\Eloquent\Model;

class IPv6Geolocation extends Model implements Geolocation
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ipv6_geolocations';

    /**
     * Indicate if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * @inheritDoc
     */
    public static function findByAddress(string $address): ?self
    {
        if ($address = ipv6_long($address)) {
            $location = static::query()->where('ip_to', '>=', $address)->first();

            if ($location && $location->ip_from <= $address) {
                return $location;
            }
        }

        return null;
    }
}
