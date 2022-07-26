<?php

namespace App\Actions\Business\OnlineStore\Coupons;

use App\Business\ProductVariation;

class AppliesSpecificProduct extends Action
{
    /**
     * @return bool
     */
    public function isCouponApplied(): bool
    {
        $isCouponApplied = false;

        foreach ($this->cart['products'] as $cartProductVariationId => $product) {
            foreach ($this->appliesToIds as $appliesToId) {
                $appliesProductVariationId = $appliesToId['variation']->getKey();

                if ($cartProductVariationId === $appliesProductVariationId) {
                    $isCouponApplied = true;

                    break;
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
            return [
                'status' => 'failed',
                'message' => 'No applied from carts'
            ];
        }

        $cartCoupons = [];

        foreach ($this->cart['products'] as $cartProductVariationId => $product) {
            foreach ($this->appliesToIds as $appliesToId) {
                $appliesProductVariationId = $appliesToId['variation']->getKey();

                if ($cartProductVariationId === $appliesProductVariationId) {
                    $productModel = $this->business->productVariations()
                        ->where('id', $cartProductVariationId)
                        ->first();

                    if (!$productModel instanceof ProductVariation) {
                        continue;
                    }

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

        return [
            'status' => 'success',
            'message' => 'coupon applied',
            'coupon' => $this->coupon,
            'coupon_information' => $cartCoupons,
            'is_product_price_changed' => true,
        ];
    }
}
