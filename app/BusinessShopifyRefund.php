<?php

namespace App;

use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessShopifyRefund extends Model
{
    use SoftDeletes, UsesUuid, Ownable;

    protected $fillable = ['business_id', 'id', 'gid', 'payment_id', 'request_data', 'response_data'];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];
}
