<?php

namespace App\Business;

use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPlan extends Model implements OwnableContract
{
    use Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_recurring_plans';

    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    protected $perPage = 25;

    protected $appends = [
        'readable_price',
    ];

    protected $fillable = [
        'name',
        'description',
        'cycle',
        'price',
        'currency',
        'reference'
    ];

    public function getPrice()
    {
        return getFormattedAmount($this->currency, $this->price);
    }

    public function getReadablePriceAttribute()
    {
        return getReadableAmountByCurrency($this->currency, $this->price);
    }

    /**
     * Get the tax of the recurring plan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business\Tax
     */
    public function tax() : BelongsTo
    {
        return $this->belongsTo(Tax::class, 'business_tax_id', 'id', 'tax');
    }
}
