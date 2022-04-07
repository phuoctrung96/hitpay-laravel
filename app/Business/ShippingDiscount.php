<?php

namespace App\Business;

use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class ShippingDiscount extends Model implements OwnableContract
{
    use Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_shipping_discounts';

    protected $casts = [
        'minimum_cart_amount' => 'int',
        'fixed_amount' => 'float',
        'percentage' => 'float',
        'type' => 'string',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
    protected $fillable = ['minimum_cart_amount','fixed_amount', 'percentage','type'];

    public function getPrice($currency, $amount)
    {
        return getFormattedAmount($currency, $amount);
    }
    public function getPercent($value)
    {
        $value = round($value * 100, 2);
        return $value . '%';
    }
}
