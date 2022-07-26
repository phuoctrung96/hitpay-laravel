<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\Product;
use App\Enumerations\Business\ImageGroup;
use App\Enumerations\Business\ProductStatus;
use HitPay\Image\Processor as ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductRepository
{
    /**
     * Create a new product.
     *
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Business\Product
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function store(Request $request, Business $business)
    {
        $imageValidation = [];
        if ($request->hasFile("image")) {
            if (is_array($request->image)) {
                $imageValidation = ['image.*' => ['sometimes', 'image']];
            } else {
                $imageValidation = ['image' => ['sometimes', 'image']];
            }
        }

        $data = Validator::validate($request->all(), static::validationRules($business->getKey(), [
                'currency' => [
                    'sometimes',
                    Rule::in($business->currency),
                ],
                'publish' => [
                    'required',
                    'bool',
                ],

            ] + $imageValidation));

        $productData = Arr::only($data, [
            'name',
            'headline',
            'description',
            'currency',
            'price',
            'quantity',
            'quantity_alert_level',
            'business_product_category_id',

        ]);

        if (empty($productData['currency'])) {
            $productData['currency'] = $business->currency;
        }

        $productData['business_product_category_id'] = $data['business_product_category_id'] ?? null;
        $productData['business_tax_id'] = $data['tax_id'] ?? null;
        $productData['is_pinned'] = $data['is_pinned'] ?? 0;
        $productData['price'] = getRealAmountForCurrency($productData['currency'], $productData['price']);
        $productData['quantity'] = $data['is_manageable'] ?? false ? 1 : 0;

        if ($data['publish']) {
            $productData['published_at'] = Date::now();
            $productData['status'] = ProductStatus::PUBLISHED;
        } else {
            $productData['status'] = ProductStatus::DRAFT;
        }

        $image = $data['image'] ?? null;

        if (isset($data['variation'])) {
            $i = 1;

            foreach (Arr::sort($data['variation'][0]['values'], function ($value) {
                return $value['key'];
            }) as $key) {
                $productData['variation_key_' . $i] = $key['key'];
                $variationKeyMap[$key['key']] = 'variation_value_' . $i;

                $i++;
            }

            if (isset($variationKeyMap)) {
                foreach ($data['variation'] as $variation) {
                    $variationName = collect(Arr::sort($variation['values'], function ($value) {
                        return $value['key'];
                    }))->pluck('value');

                    $temp = [
                        'business_id' => $business->id,
                        'description' => $variationName->implode(' / '),
                        'price' => getRealAmountForCurrency($business->currency, $variation['price']),
                    ];

                    if ($productData['quantity'] === 1) {
                        $temp['quantity'] = $variation['quantity'];
                        $temp['quantity_alert_level'] = $variation['quantity_alert_level'] ?? null;
                    }

                    foreach ($variation['values'] as $value) {
                        if (!isset($variationKeyMap[$value['key']])) {
                            continue 2;
                        }

                        $temp[$variationKeyMap[$value['key']]] = $value['value'];
                    }

                    $variationsData[] = $temp;
                }
            }
        } else {
            $temp = [
                'business_id' => $business->id,
                'price' => getRealAmountForCurrency($business->currency, $data['price']),
            ];

            if ($productData['quantity'] === 1) {
                $temp['quantity'] = $data['quantity'];
                $temp['quantity_alert_level'] = $data['quantity_alert_level'] ?? null;
            }

            $variationsData[] = $temp;
        }

        return DB::transaction(function () use ($business, $productData, $variationsData, $image) {
            $product = $business->products()->create($productData);

            $product->variations()->createMany($variationsData);

            if ($image) {
                if (is_array($image)) {
                    foreach ($image as $itemImage) {
                        ImageProcessor::new($business, ImageGroup::PRODUCT, $itemImage, $product)
                            ->setCaption($product->name)
                            ->process();
                    }
                } else {
                    ImageProcessor::new($business, ImageGroup::PRODUCT, $image, $product)
                        ->setCaption($product->name)
                        ->process();
                }
            }

            return $product;
        });
    }

    /**
     * Update an existing product.
     *
     * NOTE: Only product without variation.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business\Product $product
     *
     * @return \App\Business\Product
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function update(Request $request, Product $product): Product
    {
        if ($product->variations_count > 1) {
            App::abort(403, 'You can\'t update product with multiple variations through API.');
        }

        $data = Validator::validate($request->all(), static::validationRules($product->business_id));

        $productData = Arr::only($data, [
            'name',
            'headline',
            'description',
            'price',
        ]);

        $productData['business_product_category_id'] = $data['category_id'] ?? null;
        $productData['business_tax_id'] = $data['tax_id'] ?? null;
        $productData['is_pinned'] = $data['is_pinned'] ?? 0;

        $variationData = Arr::only($data, [
            'quantity',
            'quantity_alert_level',
            'price',
        ]);

        if (isset($variationData['quantity'])) {
            $productData['quantity'] = 1;
        } else {
            $productData['quantity'] = 0;
        }

        $variationData['price'] = getRealAmountForCurrency($product->currency, $variationData['price']);

        return DB::transaction(function () use ($product, $productData, $variationData) {
            $product->update($productData);

            // We use `first or fail` here because we don't want to have any chance it will update all the variations if
            // the product already have them. Choosing the first one is also not the safest way, but at least, it
            // damages the least.

            $product->variations()->firstOrFail()->update($variationData);

            return $product;
        });
    }

    /**
     * Delete a product.
     *
     * @param \App\Business\Product $product
     *
     * @return bool|null
     * @throws \Throwable
     */
    public static function delete(Product $product): ?bool
    {
        return DB::transaction(function () use ($product) : ?bool {
            return $product->delete();
        }, 3);
    }

    /**
     * Add product to a product.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Product $product
     *
     * @return \App\Business\Product
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function addImage(Request $request, Business $business, Product $product): Product
    {
        $image = Validator::validate($request->all(), [
            'image' => [
                'required',
                'image',
            ],
        ])['image'];

        return DB::transaction(function () use ($business, $product, $image) {
            ImageProcessor::new($business, ImageGroup::PRODUCT, $image, $product)
                ->setCaption($product->name)
                ->process();

            return $product;
        });
    }

    /**
     * Get the validation rules.
     *
     * @param string $businessId
     * @param array $additionalRules
     *
     * @return array
     */
    private static function validationRules(string $businessId, array $additionalRules = []): array
    {
        return sortArrayByPriorities($additionalRules + [
                'category_id' => [
                    'nullable',
                    Rule::exists('business_product_categories', 'id')->where('business_id', $businessId),
                ],
                'name' => [
                    'required',
                    'string',
                    'max:128',
                ],
                'headline' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'description' => [
                    'nullable',
                    'string',
                    'max:16777215',
                ],
                'price' => [
                    'required',
                    'decimal:0,2',
                ],
                'quantity' => [
                    'required_with:quantity_alert_level',
                    'nullable',
                    'int',
                    'min:0',
                ],
                'quantity_alert_level' => [
                    'nullable',
                    'int',
                    'min:0',
                ],
                'business_product_category_id' => [
                    'nullable',
                    'string',
                ],
                'tax_id' => [
                    'nullable',
                    Rule::exists('business_taxes', 'id')->where('business_id', $businessId),
                ],
                'is_pinned' => [
                    'nullable',
                    'bool'
                ],
                'is_manageable' => [
                    'nullable',
                    'bool'
                ],
                'variation' => [
                    'nullable',
                    'array',
                    'max:100'
                ],
                'variation.*.values' => [
                    'required_with:variation',
                    'array',
                    'max:3',
                ],
                'variation.*.values.*.key' => [
                    'required_with:variation.*.values'
                ],
                'variation.*.values.*.value' => [
                    'required_with:variation.*.values.*.key',
                    'string'
                ],
                'variation.*.quantity' => [
                    'required_if:is_manageable,true',
                    'required_with:variation.*.quantity_alert_level',
                    'int',
                    'min:0'
                ],
                'variation.*.quantity_alert_level' => [
                    'nullable',
                    'int',
                    'min:0'
                ],
                'variation.*.price' => [
                    'required_with:variation',
                    'decimal:0,2'
                ],
            ], [
            'category_id',
            'name',
            'headline',
            'description',
            'currency',
            'price',
            'tax_id',
            'quantity',
            'quantity_alert_level',
            'image',
        ]);
    }

    public static function getList(Request $request, Business $business)
    {
        $products = $business->setConnection('mysql_read')->products()->with('variations', 'images');

        if ($request->has('statuses')) {
            $statuses = $request->get('statuses', null);
            $products->where(function ($query) use ($statuses) {
                if (is_array($statuses)) {
                    $query->whereIn('status', $statuses);
                } else {
                    $query->where('status', $statuses);
                }
            });
        }

        if ($request->has('categories')) {
            $categories = $request->get('categories', null);

            $products->where(function ($query) use ($categories) {
                if (is_array($categories)) {
                    foreach ($categories as $key => $category) {
                        if (!$key)
                            $query->whereJsonContains('business_product_category_id', $category);
                        else
                            $query->orWhereJsonContains('business_product_category_id', $category);
                    }
                } else {
                    $query->whereJsonContains('business_product_category_id', $categories);
                }
            });
        }

        if ($request->has('sources')) {
            $sources = $request->get('sources', null);
            $parentIds = $business->setConnection('mysql_read')->productVariations()->whereNotNull('shopify_inventory_item_id')->pluck('parent_id')->unique();

            $products->where(function ($query) use ($sources, $parentIds) {
                if (is_array($sources)) {
                    foreach ($sources as $key => $source) {
                        if ($source === 'shopify')
                            $query->whereIn('id', $parentIds);
                        if ($source === 'wooCommerce')
                            $query->whereNull('id');
                    }
                }
            });
        }

        if ($request->has('inventory')) {
            $inventory = $request->get('inventory', null);

            $products->whereHas("variations", function ($query) use ($inventory) {
                if ($inventory === 'in_stock') {
                    $query->select('parent_id')
                        ->havingRaw('sum(quantity) > 0')
                        ->orHavingRaw('sum(quantity) is NULL')
                        ->groupBy('parent_id');
                }
                if ($inventory === 'out_of_stock') {
                    $query->select('parent_id')
                        ->whereNotNull('quantity')
                        ->havingRaw('sum(quantity) <= 0')
                        ->groupBy('parent_id');
                }
            });
        }

        if ($request->has('keywords')) {
            $keywords = $request->get('keywords');
            $keywords = is_array($keywords) ? $keywords : explode(' ', $keywords);
            $keywords = array_map(function ($value) {
                return trim($value);
            }, $keywords);
            $keywords = array_filter($keywords);
            $keywords = array_unique($keywords);

            if (count($keywords)) {
                foreach ($keywords as $keyword) {
                    $products->where('name', 'like', '%'.$keyword.'%');
                }
            } else {
                $products->whereNull('id');
            }
        }

        $products = $products->orderByDesc('created_at');

        return $products;
    }

}
