<?php

namespace App\Actions\Business\OnlineStore\Coupons;

class AppliesSpecificCategory extends Action
{
    /**
     * @return bool
     */
    public function isCouponApplied(): bool
    {
        $isCouponApplied = false;

        foreach ($this->cart['products'] as $cartProductVariationId => $product) {
            $productModel = $this->business->productVariations()
                ->with('product')
                ->where('id', $cartProductVariationId)
                ->first();

            if (!$productModel instanceof \App\Business\ProductVariation) {
                continue;
            }

            $cartProductCategories = $productModel->product->business_product_category_id;

            if (is_null($cartProductCategories)) {
                continue;
            }

            if (is_array($cartProductCategories)) {
               foreach ($cartProductCategories as $cartProductCategory) {
                    foreach ($this->appliesToIds as $appliesToId) {
                        $productCategoryId = $appliesToId['id'];

                        if ($cartProductCategory->getKey() === $productCategoryId) {
                            $isCouponApplied = true;

                            break;
                        }
                    }
                }
            }
        }

        return $isCouponApplied;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function process(): array
    {
        $this->validateCoupon();

        if (!$this->isCouponApplied()) {
            $this->cart['is_applied_coupon'] = false;

            return [
                'status' => 'failed',
                'message' => 'No applied from carts',
            ];
        }

        $cartCoupons = [];

        foreach ($this->cart['products'] as $cartProductVariationId => $product) {
            $productModel = \App\Business\ProductVariation::with('product')
                ->where('id', $cartProductVariationId)
                ->first();

            if (!$productModel instanceof \App\Business\ProductVariation) {
                continue;
            }

            $cartProductCategories = $productModel->product->business_product_category_id;

            if (is_null($cartProductCategories)) {
                continue;
            }

            foreach ($cartProductCategories as $cartProductCategory) {
                foreach ($this->appliesToIds as $appliesToId) {
                    $productCategoryId = $appliesToId['id'];

                    if ($cartProductCategory->getKey() === $productCategoryId) {
                        $productPrice = $productModel->price;

                        $isCouponPercent = !$this->coupon->fixed_amount;

                        $couponAmount = $isCouponPercent ? $this->coupon->percentage : $this->coupon->fixed_amount;

                        $productPriceUpdated = $productPrice - $couponAmount;

                        if ($isCouponPercent) {
                            $productPriceUpdated = $productPrice - ($productPrice * $couponAmount);
                        }

                        $cartCoupons[] = [
                            'product_variation_id' => $cartProductVariationId,
                            'product_price' => $productPrice,
                            'coupon_name' => $this->coupon->name,
                            'coupon_amount' => $couponAmount,
                            'is_coupon_in_percent' => $isCouponPercent,
                            'product_price_changed' => $productPriceUpdated
                        ];
                    }
                }
            }
        }

        return [
            'status' => 'success',
            'message' => 'coupon applied',
            'coupon' => $this->coupon,
            'coupon_information' => $cartCoupons,
            'is_product_price_changed' => true,
        ];
    }
}
