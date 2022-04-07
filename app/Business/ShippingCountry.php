<?php

namespace App\Business;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingCountry extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'business_shipping_countries';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the shipping for this country.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business\Shipping
     */
    public function shipping() : BelongsTo
    {
        return $this->belongsTo(Shipping::class, 'business_shipping_id', 'id', 'shipping');
    }
}
