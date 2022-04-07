<?php

namespace App;

use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessShopifyStore extends Model
{
    protected $fillable = [
        'id',
        'business_id',
        'shopify_id',
        'shopify_name',
        'shopify_domain',
        'shopify_token',
        'shopify_data',
    ];

    use SoftDeletes, UsesUuid;

    const MAX_STORES = 5;

    const PAGINATE_NUMBER = 5;

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
