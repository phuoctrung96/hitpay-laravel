<?php

namespace App\Business;

use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class OrderedDiscount extends Model
{
    use UsesUuid;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'business_ordered_discounts';

    protected $casts = [
        'discount_data' => 'array',
    ];

    protected $guarded = [
        //
    ];
}
