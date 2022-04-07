<?php

namespace App;

use App\Business\ProductBase;
use HitPay\Business\Ownable as BusinessOwnable;
use HitPay\Model\UsesUuid;
use HitPay\User\Ownable as UserOwnable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use BusinessOwnable, UserOwnable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cart_items';

    /**
     * Get the business product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business\ProductBase
     */
    public function product() : BelongsTo
    {
        return $this->belongsTo(ProductBase::class, 'business_product_id', 'id', 'product');
    }
}
