<?php

namespace App\Business;

use Exception;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int quantity
 * @property int unit_price
 * @property string name
 * @property int discount_amount
 * @property string stock_keeping_unit
 * @property int business_product_id
 * @property int price
 * @property float tax_rate
 * @property float tax_amount
 */
class OrderedProduct extends Model
{
    use UsesUuid;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'business_ordered_products';

    protected $casts = [
        'tax_amount' => 'int',
        'price' => 'int',
        'discount_amount' => 'int',
        'quantity' => 'int',
        'tax_rate' => 'decimal:4',
    ];

    protected $guarded = [
        //
    ];

    /**
     * @param $attribute
     *
     * @return string
     * @throws \Exception
     */
    public function getAmountFor($currency, $attribute)
    {
        switch ($attribute) {

            case 'price':
                return getFormattedAmount($currency, $this->price);

            case 'unit_price':
                return getFormattedAmount($currency, $this->unit_price);

            case 'discount_amount':
                return getFormattedAmount($currency, $this->discount_amount);

            case 'tax_amount':
                return getFormattedAmount($currency, $this->tax_amount);
        }
        throw new Exception('Invalid attribute: '.$attribute);
    }

    public function image() : BelongsTo
    {
        return $this->belongsTo(Image::class, 'business_image_id', 'id', 'image');
    }
}
