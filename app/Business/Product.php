<?php

namespace App\Business;

use App\Business\ProductCategory;
use App\Shortcut;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends ProductBase
{
    /**
     * The relationship counts that should be eager loaded on every query.
     *
     * @var array
     */
    protected $withCount = [
        'variations',
        'images',
    ];

    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    protected $perPage = 25;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'parent_id',
        'variation_value_1',
        'variation_value_2',
        'variation_value_3',
        'active',
    ];

    // price inclusive of tax.

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('is_product', function (Builder $builder): void {
            $builder->whereNull('parent_id');
        });

        static::creating(function (self $model) {
            $shortcut = $model->shortcut()->create([
                'route_name' => 'shop.product',
                'parameters' => [
                    'business' => $model->business_id,
                    'product_id' => $model->getKey(),
                ],
            ]);

            $model->shortcut_id = $shortcut->getKey();
        });

        static::deleting(function (self $model): void {
            $model->variations()->each(function (Model $model): void {
                $model->delete();
            });
        });

        static::retrieved(function (self $model): void {
            if (json_decode($model->business_product_category_id)) {
                $arr = [];

                foreach (json_decode($model->business_product_category_id) as $category_id) {
                    if (ProductCategory::find($category_id))
                        $arr[] = ProductCategory::find($category_id);
                }

                $model->business_product_category_id = $arr;
            }
            else{
                $arr = null;

                if (ProductCategory::find($model->business_product_category_id)) {
                    $arr[] = ProductCategory::find($model->business_product_category_id);
                }

                $model->business_product_category_id = $arr;
            }
        });
    }

    /**
     * @param $attribute
     * @param null $default
     * @param bool $forceDefault
     *
     * @return mixed|string|null
     */
    public function display($attribute, $default = null, bool $forceDefault = false)
    {
        switch ($attribute) {

            case 'image':
                if ($this->shopify_id) {
                    if (isset($this->shopify_data['image']['src'])) {
                        return $this->shopify_data['image']['src'];
                    } elseif ($forceDefault) {
                        return $default;
                    }

                    return asset('hitpay/images/product.jpg');
                } else {
                    $image = $this->images->first();

                    if ($image) {
                        return $image->getUrl();
                    } elseif ($forceDefault) {
                        return $default;
                    }

                    return asset('hitpay/images/product.jpg');
                }
            case 'images':
                if ($this->shopify_id) {
                    if (isset($this->shopify_data['image']['src'])) {
                        return $this->shopify_data['image']['src'];
                    } elseif ($forceDefault) {
                        return $default;
                    }

                    return asset('hitpay/images/product.jpg');
                } else {
                    $images = $this->images;

                    if (!$images->isEmpty()) {
                        foreach ($images as $image) {
                            $imagesUrl[] = $image->getUrl();
                        }
                        return $imagesUrl;
                    } elseif ($forceDefault) {
                        return $default;
                    }
                    return asset('hitpay/images/product.jpg');
                }
            case 'price':
                $highest = $this->variations->max('price');
                $lowest = $this->variations->min('price');
                $lowest = is_null($lowest) ? 0 : $lowest;
                $highest = is_null($highest) ? 0 : $highest;
                if ($highest === $lowest) {
                    return getFormattedAmount($this->currency, $lowest);
                }

                return getFormattedAmount($this->currency, $lowest) . ' - ' . getFormattedAmount($this->currency,
                        $highest);
        }

        return $default;
    }

    public function isManageable()
    {
        return $this->quantity === 1;
    }

    public function hasVariations()
    {
        return $this->variations_count > 1;
    }

    /**
     * Get the variations of the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\ProductVariation|\App\Business\ProductVariation[]
     */
    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class, 'parent_id', 'id');
    }

    /**
     * Get the shortcut of the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Shortcut
     */
    public function shortcut(): BelongsTo
    {
        return $this->belongsTo(Shortcut::class, 'shortcut_id', 'id');
    }

    public function isAvailable()
    {
        if (!$this->isPublished()) {
            return false;
        }

        $this->load('images', 'variations');

        if ($this->isManageable()) {
            if ($this->variations_count > 1) {
                $callback = function (ProductVariation $variation) {
                    if ($variation->quantity === null) {
                        return 1;
                    }

                    return $variation->quantity;
                };

                if ($this->variations->sum($callback) < 1) {
                    return false;
                }
            } else {
                $variation = $this->variations->first();

                if (!$variation instanceof ProductVariation) {
                    return false;
                } elseif ($variation->quantity < 1) {
                    return false;
                }
            }
        }

        return true;
    }
}
