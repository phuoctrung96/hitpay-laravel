<?php

namespace App\Business;

use App\Business\HotglueProductTracker;
use App\Business\ProductCategory;
use App\Shortcut;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;

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

    public function isShopify()
    {
        $itemIds = $this->variations()->pluck('shopify_inventory_item_id')->filter();
        if ($itemId = $this->shopify_inventory_item_id) {
            $itemIds[] = $itemId;
        }
        $hotglueProductTracker = HotglueProductTracker::whereIn('item_id', $itemIds)->where('is_shopify', true)->first();
        return $hotglueProductTracker ? true : false;
    }

    public function getProductObject()
    {
        if (!$this->shortcut_id) {
            $shortcut = $this->shortcut()->create([
                'route_name' => 'shop.product',
                'parameters' => [
                    'business' => $this->business_id,
                    'product_id' => $this->getKey(),
                ],
            ]);

            $this->shortcut_id = $shortcut->getKey();
            $this->save();
        }
        $data['id'] = $this->id;
        $data['name'] = $this->name;
        $data['description'] = $this->description;
        $data['currency'] = $this->currency;
        $data['price'] = $this->price;
        $data['stock_keeping_unit'] = $this->stock_keeping_unit;
        $data['categories'] = $this->business_product_category_id;
        $data['readable_price'] = $this->readable_price;
        $data['is_manageable'] = $this->quantity > 0;
        $data['is_published'] = $this->published_at instanceof Carbon;
        $data['status'] = $this->status;
        $data['has_variations'] = $this->variations_count > 1;
        $data['variations_count'] = $this->variations_count;
        $data['image_display'] = $this->image_display;
        $data['shopify_inventory_item_id'] = $this->shopify_inventory_item_id;
        $data['checkout_url'] = $this->shortcut_id
            ? URL::route('shortcut', $this->shortcut_id)
            : URL::route('shop.product', [
                $this->business_id,
                $this->getKey(),
            ]);
        if ($this->variations_count > 1) {
            $data['variation_types'] = array_filter([
                $this->variation_key_1,
                $this->variation_key_2,
                $this->variation_key_3,
            ]);
        } elseif ($data['is_manageable'] && isset($this->variations[0])) {
            $data['quantity'] = $this->variations[0]->quantity;
            $data['quantity_alert_level'] = $this->variations[0]->quantity_alert_level;
        }

        $data['variations'] = [];

        foreach ($this->variations as $variation) {
            $variationData = [
                'id' => $variation->id,
                'description' => $variation->description,
                'values' => [
                    [
                        'key' => $this->variation_key_1,
                        'value' => $variation->variation_value_1,
                    ],
                    [
                        'key' => $this->variation_key_2,
                        'value' => $variation->variation_value_2,
                    ],
                    [
                        'key' => $this->variation_key_3,
                        'value' => $variation->variation_value_3,
                    ],
                ],
                'price' => getReadableAmountByCurrency($this->currency, $variation->price),
            ];

            if ($data['is_manageable']) {
                $variationData['quantity'] = $variation->quantity;
                $variationData['quantity_alert_level'] = $variation->quantity_alert_level;
            }

            $data['variations'][] = $variationData;
        }

        if ($this->relationLoaded('images')) {
            foreach ($this->images as $image) {
                $data['image'][] = [
                    'id' => $image->getKey(),
                    'url' => $image->getUrl(),
                ];
            }
        }

        $data['is_pinned'] = $this->is_pinned;
        $data['created_at'] = $this->created_at->toAtomString();
        $data['updated_at'] = $this->updated_at->toAtomString();

        return $data;
    }
}
