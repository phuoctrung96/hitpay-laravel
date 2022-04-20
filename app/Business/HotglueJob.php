<?php

namespace App\Business;

use Illuminate\Database\Eloquent\Model;

class HotglueJob extends Model
{
    public const FAILED = 'SYNC_FAILED';
    public const CREATED = 'JOB_CREATED';
    public const COMPLETED = 'JOB_COMPLETED';
    public const QUEUED = 'QUEUED';
    public const SYNCED = 'SYNCED';
    public const INVALID = 'INVALID';
    public const ECOMMERCE = 'ecommerce';
    public const PRODUCTS = 'products';
    public const SHOPIFY = 'shopify';
    public const WOOCOMMERCE = 'woocommerce';

    protected $guarded = [];

    public function hotglueIntegration()
    {
        return $this->belongsTo('App\Business\HotglueIntegration', 'hotglue_integration_id', 'id', 'hotglue_integration');
    }
}
