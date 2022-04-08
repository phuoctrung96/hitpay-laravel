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

    protected $fillable = ['seller_notes', 'shop_state', 'enable_datetime', 'slots', 'can_pick_up', 'is_redirect_order_completion', 'url_redirect_order_completion'];

}
