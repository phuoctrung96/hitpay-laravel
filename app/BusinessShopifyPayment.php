<?php

namespace App;

use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessShopifyPayment extends Model
{
    use SoftDeletes, UsesUuid;

    protected $fillable = ['business_id', 'id', 'gid', 'request_id', 'request_data'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
