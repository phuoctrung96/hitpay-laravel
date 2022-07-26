<?php

namespace App\Actions\Business\OnlineStore\Discounts;

use App\Business\ProductVariation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades;

class AppliesSpecificProduct extends Action
{
    /**
     * @return array
     * @throws \Exception
     */
    private function getProductsCartWithAmount(): array
    {
        $productCarts = [];

        if (!isset($this->cart['products'])) {
            throw new \Exception("no have products key on carts with business ID $this->business->getKey()");
        }

        foreach ($this->cart['products'] as $key => $value) {
            if (!isset($value['variation_id'])) {
                continue;
            }

            if (!isset($value['quantity'])) {
                continue;
            }

            $variation = $this->business->productVariations()
                ->where('id', $value['variation_id'])
                ->first();

            if (!$variation instanceof ProductVariation) {
                continue;
            }

            $cartProductCategories = $variation->product->business_product_category_id;

            if (is_null($cartProductCategories)) {
                continue;
            }

            $productCarts[] = [
                'product_variation_id' => $value['variation_id'],
                'total_cart_amount' => (int) bcmul($value['quantity'], $variation->price)
            ];
        }

        return $productCarts;
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

        $productsCartWithAmount = $this->getProductsCartWithAmount();

        $anyApplied = false;
        $result = [];

        if ($this->discounts->count() !== 1) {
            $businessDiscounts = $this->discounts->sortByDesc('minimum_cart_amount');

            foreach ($productsCartWithAmount as $productCartAmount) {
                foreach ($businessDiscounts as $discount) {
                    if ($discount->applies_to_ids === null) {
                        Facades\Log::critical('There is discounts with ID ($discount->getKey()) that no have applies_to_ids data');

                        continue;
                    }

                    $appliesToIds = collect($discount->applies_to_ids)->pluck('variation.id')->toArray();

                    if (
                        in_array($productCartAmount['product_variation_id'], $appliesToIds) &&
                        $discount->minimum_cart_amount <= $productCartAmount['total_cart_amount']
                    ) {
                        if ($discount->fixed_amount) {
                            $discountAmount = $discount->fixed_amount;
                        } else if ($discount->percentage) {
                            $discountAmount = $productCartAmount['total_cart_amount'] * $discount->percentage;
                        } else {
                            Log::critical("There is discount with amount type not set as fixed or percentage");

                            $discountAmount = 0; // should never come
                        }

                        $result[] = [
                            'product_variation_id' => $productCartAmount['product_variation_id'],
                            'total_cart_amount' => $productCartAmount['total_cart_amount'],
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

            foreach ($productsCartWithAmount as $productCartAmount) {
                $appliesToIds = collect($discount->applies_to_ids)->pluck('variation.id')->toArray();

                if (
                    in_array($productCartAmount['product_variation_id'], $appliesToIds) &&
                    $discount->minimum_cart_amount <= $productCartAmount['total_cart_amount']
                ) {
                    if ($discount->fixed_amount) {
                        $discountAmount = $discount->fixed_amount;
                    } else if ($discount->percentage) {
                        $discountAmount = $productCartAmount['price'] * $discount->percentage;
                    } else {
                        Log::critical("There is discount with amount type not set as fixed or percentage");

                        $discountAmount = 0; // should never come
                    }

                    $result[] = [
                        'product_variation_id' => $productCartAmount['product_variation_id'],
                        'total_cart_amount' => $productCartAmount['total_cart_amount'],
                        'discount_applied' => $discount,
                        'discount_amount' => $discountAmount
                    ];

                    $anyApplied = true;
                }
            }
        }

        if (!$anyApplied) {
            return [
                'status' => 'failed',
                'message' => 'no discount applies'
            ];
        }

        return [
            'status' => 'success',
            'message' => 'discount applied',
            'discount_information' => $result
        ];
    }
}
