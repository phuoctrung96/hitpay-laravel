<?php

namespace App\Business;

use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class BusinessShopSettings extends Model implements OwnableContract
{
    use Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_store_settings';

    protected $casts = [
        'get_started' => 'array'
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
      "get_started" => '{"hide_get_started" : 0,"store_url" : 0,"theme" : 0,"products" : 0,"shipping_and_pickup" : 0}'
    ];

    protected $fillable = ['seller_notes', 'shop_state', 'enable_datetime', 'slots', 'can_pick_up', 'is_redirect_order_completion', 'url_redirect_order_completion', 'url_facebook', 'url_instagram', 'url_instagram', 'url_twitter', 'get_started'];

    protected static function boot() : void
    {
        parent::boot();

        static::retrieved(function (self $model) : void {
            if ($model->get_started === null)
                $model->get_started = json_decode('{"hide_get_started" : 0,"store_url" : 0,"theme" : 0,"products" : 0,"shipping_and_pickup" : 0}');
        });
    }
}
