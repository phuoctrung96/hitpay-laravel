<?php

namespace App\Business;

use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class Cashback extends Model implements OwnableContract
{
    use Ownable, UsesUuid;

    public static $cashback_admin_fee = 0.50;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_checkout_rebates';

    protected $casts = [
        'fixed_amount' => 'float',
        'percentage' => 'float',
        'minimum_order_amount' => 'int',
        'maximum_cashback' => 'int',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'name' => 'string',
        'channel' => 'string',
        'payment_provider_charge_type' => 'string'
    ];
    protected $fillable = ['fixed_amount', 'percentage', 'name', 'minimum_order_amount', 'maximum_cashback', 'channel', 'payment_provider_charge_type', 'ends_at','cashback_admin_fee', 'enabled'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $model) {
            $model->cashback_admin_fee = getRealAmountForCurrency('sgd', self::$cashback_admin_fee);
        });
    }

    public function getPrice($currency, $amount)
    {
        return getFormattedAmount($currency, $amount);
    }

    public function getPercent($value)
    {
        $value = round($value * 100, 2);
        return $value . '%';
    }

    public function display($attribute)
    {
        switch ($attribute) {
            case 'fixed_amount':
                return getFormattedAmount('sgd', $this->fixed_amount);
            case 'minimum_order_amount':
                return getFormattedAmount('sgd', $this->minimum_order_amount);
            case 'maximum_cashback':
                return getFormattedAmount('sgd', $this->maximum_cashback);
        }
    }
}
