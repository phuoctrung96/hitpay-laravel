<?php

namespace App\Business;

use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model implements OwnableContract
{
    use Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_discounts';

    protected $casts = [
        'minimum_cart_amount' => 'int',
        'fixed_amount' => 'float',
        'percentage' => 'float',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'name' => 'string',
        'is_promo_banner' => 'bool',
        'banner_text' => 'string'
    ];
    protected $fillable = ['minimum_cart_amount','fixed_amount', 'percentage', 'name','is_promo_banner', 'banner_text'];

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
