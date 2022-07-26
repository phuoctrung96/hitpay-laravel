<?php

namespace App\Actions\Business\OnlineStore\Discounts;

use App\Business\ProductVariation;
use Illuminate\Support\Facades;

class AppliesSpecificCategory extends Action
{

    /**
     * @return array
     * @throws \Exception
     */
    private function getCategoriesCartWithAmount(): array
    {
        $productCarts = [];

        if (!isset($this->cart['products'])) {
            throw new \Exception("no have products key on carts with business ID $this->business->getKey()");
        }

        // get category from cart product and calculate cart amount by product first
        $categoryIds = [];

        foreach ($this->cart['products'] as $key => $value) {
            $productCategoryIds = [];

            if (!isset($value['variation_id'])) {
                continue;
            }

            if (!isset($value['quantity'])) {
                continue;
            }

            $variation = $this->business->productVariations()->with('product')
                ->where('id', $value['variation_id'])
                ->first();

            if (!$variation instanceof ProductVariation) {
                continue;
            }

            $cartProductCategories = $variation->product->business_product_category_id;

            if (is_null($cartProductCategories)) {
                continue;
            }

            if (is_array($cartProductCategories)) {
                foreach ($cartProductCategories as $cartAmountPerCategory) {
                    $categoryIds[] = $cartAmountPerCategory->getKey();
                    $productCategoryIds[] = $cartAmountPerCategory->getKey();
                }
            }

            $productCarts[] = [
                'product_category_ids' => $productCategoryIds,
                'total_cart_amount' => (int) bcmul($value['quantity'], $variation->price),
            ];
        }

        // calculate total cart amount by category ids
        $cartAmountPerCategories = [];

        $categoryIds = array_unique($categoryIds);

        foreach ($categoryIds as $categoryId) {
            $amount = 0;

            foreach ($productCarts as $productCart) {
                if (in_array($categoryId, $productCart['product_category_ids'])) {
                    $amount = $amount + $productCart['total_cart_amount'];
                }
            }

            $cartAmountPerCategories[] = [
                'product_category_id' => $categoryId,
                'total_cart_amount' => $amount,
                'discount_applied' => [],
                'discount_amount' => 0,
            ];
        }

        return $cartAmountPerCategories;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function process(): array
    {
        if ($this->discounts->count() <= 0) {
            throw new \Exception("Discounts collection not yet set with Business ID $this->business->getKey()");
        }

        $categoriesCartWithAmount = $this->getCategoriesCartWithAmount();

        $anyApplied = false;
        $result = [];

        if ($this->discounts->count() !== 1) {
            $businessDiscounts = $this->discounts->sortByDesc('minimum_cart_amount');

            foreach ($categoriesCartWithAmount as $categoryCartAmount) {
                foreach ($businessDiscounts as $discount) {
                    if ($discount->applies_to_ids === null) {
                        Facades\Log::critical('There is discounts with ID ($discount->getKey()) that no have applies_to_ids data');

                        continue;
                    }

                    $appliesToIds = collect($discount->applies_to_ids)->pluck('id')->toArray();

                    if (
                        in_array($categoryCartAmount['product_category_id'], $appliesToIds) &&
                        $discount->minimum_cart_amount <= $categoryCartAmount['total_cart_amount']
                    ) {
                        if ($discount->fixed_amount) {
                            $discountAmount = $discount->fixed_amount;
                        } else if ($discount->percentage) {
                            $discountAmount = $categoryCartAmount['total_cart_amount'] * $discount->percentage;
                        } else {
                            Facades\Log::critical("There is discount with amount type not set as fixed or percentage");

                            $discountAmount = 0; // should never come
                        }

                        $result[] = [
                            'product_category_id' => $categoryCartAmount['product_category_id'],
                            'total_cart_amount' => $categoryCartAmount['total_cart_amount'],
                            'discount_applied' => $discount,
                            'discount_amount' => $discountAmount
                        ];

                        $anyApplied = true;
                    }
                }
            }
        } else {
            $discount = $this->discounts->first();

            if ($discount->applies_to_ids === null) {
                Facades\Log::critical('There is discounts with ID ($discount->getKey()) that no have applies_to_ids data');

                return [
                    'status' => 'failed',
                    'message' => 'no discount applied',
                ];
            }

            foreach ($categoriesCartWithAmount as $categoryCartAmount) {
                $appliesToIds = collect($discount->applies_to_ids)->pluck('id')->toArray();

                if (
                    in_array($categoryCartAmount['product_category_id'], $appliesToIds) &&
                    $discount->minimum_cart_amount <= $categoryCartAmount['total_cart_amount']
                )
                {
                    if ($discount->fixed_amount) {
                        $discountAmount = $discount->fixed_amount;
                    } else if ($discount->percentage) {
                        $discountAmount = $categoryCartAmount['total_cart_amount'] * $discount->percentage;
                    } else {
                        Facades\Log::critical("There is discount with amount type not set as fixed or percentage");

                        $discountAmount = 0; // should never come
                    }

                    $result[] = [
                        'product_category_id' => $categoryCartAmount['product_category_id'],
                        'total_cart_amount' => $categoryCartAmount['total_cart_amount'],
                        'discount_applied' => $discount,
                        'discount_amount' => $discountAmount
                    ];

                    $anyApplied = true;

                    break;
                }
            }
        }

        if (!$anyApplied) {
            return [
                'status' => 'failed',
                'message' => 'no discount applied',
            ];
        }

        return [
            'status' => 'success',
            'message' => 'discount applied',
            'discount_information' => $result,
        ];
    }
}
