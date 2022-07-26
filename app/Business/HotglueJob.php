<?php

namespace App\Business;

use Illuminate\Database\Eloquent\Model;

class HotglueJob extends Model
{
    public const FAILED = 'SYNC_FAILED';
    public const CREATED = 'JOB_CREATED';
    public const COMPLETED = 'JOB_COMPLETED';
    public const CANCELLED = 'JOB_CANCELLED';
    public const QUEUED = 'QUEUED';
    public const QUEUE_FAILED = 'QUEUE_FAILED';
    public const SYNCED = 'SYNCED';
    public const INVALID = 'INVALID';
    public const ECOMMERCE = 'ecommerce';
    public const PRODUCTS = 'products';
    public const SHOPIFY = 'shopify';
    public const WOOCOMMERCE = 'woocommerce';
    public const INITIAL_SYNC = 1;
    public const NOW_SYNC = 2;
    public const QUANTITY_SYNC = 3;
    public const ORDER_SYNC = 4;
    public const SCHEDULED_SYNC = 5;

    protected $guarded = [];

    public function hotglueIntegration()
    {
        return $this->belongsTo('App\Business\HotglueIntegration', 'hotglue_integration_id', 'id', 'hotglue_integration');
    }
}
