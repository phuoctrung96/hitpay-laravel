<?php

namespace App\Business;

use HitPay\Business\BasicLogging;
use HitPay\Business\Contracts\BasicLogging as BasicLoggingContract;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model implements BasicLoggingContract, OwnableContract
{
    use BasicLogging, Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_product_categories';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'bool',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'active',
    ];

    /**
     * The loggable attributes list.
     *
     * @var array
     */
    protected $loggableAttributes = [
        'name',
        'active',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) : void {
            if (!array_key_exists('active', $model->attributes)) {
                $model->setAttribute('active', true);
            }
        });

        static::deleting(function (self $model) : void {
            $products = $model->business->products;
            foreach ($products as $product) {
                $category_arr = [];
                if ($product->business_product_category_id) {
                    foreach ($product->business_product_category_id as $product_category) {
                        if ($product_category->id != $model->id) {
                            array_push($category_arr, $product_category->id);
                        }
                    }
                    $product->business_product_category_id = json_encode($category_arr);
                    $product->save();
                }
            }
        });
    }

    /**
     * Get the products.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Product|\App\Business\Product[]
     */
    public function products() : HasMany
    {
        return $this->hasMany(Product::class, 'business_product_category_id', 'id');
    }

    /**
     * Get the logging group.
     *
     * @return string
     */
    public function getLoggingGroup() : string
    {
        return 'operation';
    }
}
