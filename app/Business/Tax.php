<?php

namespace App\Business;

use HitPay\Business\BasicLogging;
use HitPay\Business\Contracts\BasicLogging as BasicLoggingContract;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tax extends Model implements BasicLoggingContract, OwnableContract
{
    use BasicLogging, Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_taxes';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'applies_locally' => 'bool',
        'applies_overseas' => 'bool',
        'rate' => 'decimal:4',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        //
    ];

    /**
     * The loggable attributes list.
     *
     * @var array
     */
    protected $loggableAttributes = [
        'name',
        'applies_locally',
        'applies_overseas',
        'rate',
    ];

    // todo boot, update all leh. diao

    /**
     * Get the products with this tax.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Product|\App\Business\Product[]
     */
    public function products() : HasMany
    {
        return $this->hasMany(Product::class, 'business_tax_id', 'id');
    }

    /**
     * Get the recurring plans with this tax.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\SubscriptionPlan|\App\Business\SubscriptionPlan[]
     */
    public function recurringBillings() : HasMany
    {
        return $this->hasMany(SubscriptionPlan::class, 'business_tax_id', 'id');
    }

    /**
     * Get the shippings with this tax.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Shipping|\App\Business\Shipping[]
     */
    public function shippings() : HasMany
    {
        return $this->hasMany(Shipping::class, 'business_tax_id', 'id');
    }

    /**
     * Get the logging group.
     *
     * @return string
     */
    public function getLoggingGroup() : string
    {
        return 'operation';
    }
}
