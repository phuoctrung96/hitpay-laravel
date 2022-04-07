<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\Product;
use App\Enumerations\Business\ImageGroup;
use HitPay\Image\Processor as ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductRepository
{
    /**
     * Create a new product.
     *
     * NOTE: Without variation.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Business\Product
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function store(Request $request, Business $business) : Product
    {
        if ($request->hasFile("image")) {
            if (is_array($request->image)) {
                $imageValidation = ['image.*' => ['sometimes','image']];
            } else {
                $imageValidation = ['image' => ['sometimes','image']];
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
            ]
        ] + $imageValidation));

        $productData = Arr::only($data, [
            'name',
            'headline',
            'description',
            'currency',
            'price',
        ]);

        if (empty($productData['currency'])) {
            $productData['currency'] = $business->currency;
        }

        $productData['business_product_category_id'] = $data['category_id'] ?? null;
        $productData['business_tax_id'] = $data['tax_id'] ?? null;

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

        $variationData['business_id'] = $business->getKey();
        $productData['price'] = getRealAmountForCurrency($productData['currency'], $productData['price']);
        $variationData['price'] = getRealAmountForCurrency($productData['currency'], $variationData['price']);

        if ($data['publish']) {
            $productData['published_at'] = Date::now();
            $variationData['published_at'] = $productData['published_at'];
        }

        $image = $data['image'] ?? null;

        return DB::transaction(function () use ($business, $productData, $variationData, $image) {
            $product = $business->products()->create($productData);

            $product->variations()->create($variationData);

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
    public static function update(Request $request, Product $product) : Product
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
    public static function delete(Product $product) : ?bool
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
    public static function addImage(Request $request, Business $business, Product $product) : Product
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
    private static function validationRules(string $businessId, array $additionalRules = []) : array
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
                'tax_id' => [
                    'nullable',
                    Rule::exists('business_taxes', 'id')->where('business_id', $businessId),
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
}
