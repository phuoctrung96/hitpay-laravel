<?php

namespace App\Business;

use App\Enumerations\Business\PromotionAppliesToType;
use App\Http\Resources\Business\Product as ProductResource;
use App\Http\Resources\Business\ProductVariation as ProductVariationResource;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model implements OwnableContract
{
    use Ownable, UsesUuid, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_discounts';

    protected $casts = [
        'minimum_cart_amount' => 'int',
        'fixed_amount' => 'float',
        'percentage' => 'float',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'name' => 'string',
        'is_promo_banner' => 'bool',
        'banner_text' => 'string'
    ];
    protected $fillable = [
        'minimum_cart_amount','fixed_amount', 'percentage', 'name','is_promo_banner', 'banner_text',
        'discount_type',
    ];

    protected $appends = ['applies_to_ids'];

    /**
     * @return array
     */
    public function getAppliesToIdsAttribute(): array
    {
        if ($this->discount_type === PromotionAppliesToType::SPECIFIC_PRODUCTS) {
            $products = $this->promotions()->pluck('applies_to_id')->toArray();

            $added_products = [];

            if ($products) {
                foreach ($products as $product) {
                    $business = $this->business;

                    if (!$business) {
                        continue;
                    }

                    $productVariation = $this->business->productVariations()->with('product')->find($product);

                    if ($productVariation) {
                        $added_products[] = [
                            'product' => new ProductResource($productVariation->product),
                            'variation' => new ProductVariationResource($productVariation)
                        ];
                    }
                }
            }

            return $added_products;
        } else if ($this->discount_type === PromotionAppliesToType::SPECIFIC_CATEGORIES) {
            $categories = $this->promotions()->pluck('applies_to_id')->toArray();

            $added_categories = [];

            if ($categories) {
                foreach ($categories as $category) {
                    $business = $this->business;

                    if (!$business) {
                        continue;
                    }

                    $productCategory = $this->business->productCategories()->find($category);

                    if ($productCategory) {
                        $added_categories[] = [
                            'id' => $productCategory->getKey(),
                            'name' => $productCategory->name,
                        ];
                    }
                }
            }

            return $added_categories;
        } else {
            return [];
        }
    }

    public function getPrice($currency, $amount)
    {
        return getFormattedAmount($currency, $amount);
    }
    public function getPercent($value)
    {
        $value = round($value * 100, 2);
        return $value . '%';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class, 'promotion_id', 'id');
    }
}
