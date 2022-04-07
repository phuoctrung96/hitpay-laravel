<?php

namespace App\Business;

use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Image\HasImages as HasImagesContract;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ProductBase extends Model implements HasImagesContract, OwnableContract
{
    use Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_products';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'int',
        'shopify_data' => 'array',
        'published_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected $appends = [
        'readable_price',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function (self $model) : void {
            $model->images()->each(function (Image $model) : void {
                $model->delete();
            });
        });
    }

    public function getReadablePriceAttribute() : ?string
    {
        if ($this->currency && $this->price) {
            return getReadableAmountByCurrency($this->currency, $this->price);
        }

        return null;
    }

    /**
     * Get mutator for "is published" attribute.
     *
     * @return bool
     */
    public function getIsPublishedAttribute() : bool
    {
        return !is_null($this->published_at);
    }

    public function isProduct()
    {
        return is_null($this->parent_id);
    }

    public function isVariation()
    {
        return !$this->isProduct();
    }

    /**
     * Get the category of the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business\ProductCategory
     */
    public function category() : BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'business_product_category_id', 'id', 'tax');
    }

    /**
     * Get the images of the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany|\App\Business\Image|\App\Business\Image[]
     */
    public function images() : MorphMany
    {
        return $this->morphMany(Image::class, 'images', 'business_associable_type', 'business_associable_id', 'id');
    }

    /**
     * Get the tax of the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business\Tax
     */
    public function tax() : BelongsTo
    {
        return $this->belongsTo(Tax::class, 'business_tax_id', 'id', 'tax');
    }

    /**
     * Get the parent of the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business\ProductBase
     */
    public function parent() : BelongsTo
    {
        return $this->belongsTo(ProductBase::class, 'parent_id', 'id', 'tax');
    }

    /**
     * Indicate if product is published.
     *
     * @return bool
     */
    public function isPublished() : bool
    {
        return $this->is_published;
    }
}
