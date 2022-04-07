<?php

namespace App\Business;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariation extends ProductBase
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'name',
        'headline',
        'business_product_category_id',
        'variation_key_1',
        'variation_key_2',
        'variation_key_3',
        'currency',
        'business_tax_id',
        'published_at',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('is_variation', function (Builder $builder) : void {
            $builder->whereNotNull('parent_id');
        });
    }

    /**
     * Get the product of the variation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business\Product
     */
    public function product() : BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_id', 'id', 'product');
    }
}
