<?php

namespace App\Business;

use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_promotions';

    protected $fillable = [
        'promotion_type', 'promotion_id', 'applies_to_type', 'applies_to_id'
    ];
}
