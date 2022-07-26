<?php

namespace App\Actions\Business\OnlineStore\Discounts;

use App\Business\ProductVariation;
use Illuminate\Support\Facades\Log;

class AppliesAllProduct extends Action
{
    /**
     * @return int
     */
    private function getCartAmount(): int
    {
        if (!isset($this->cart['products'])) {
            Log::critical("no have products key on carts with business ID $this->business->getKey()");

            return 0;
        }

        $totalCartAmount = 0;

        foreach ($this->cart['products'] as $key => $value) {
            if (!isset($value['variation_id'])) {
                continue;
            }

            if (!isset($value['quantity'])) {
                continue;
            }

            $variation = $this->business->productVariations()->find($value['variation_id']);

            if (!$variation instanceof ProductVariation) {
                continue;
            }

            $totalCartAmount = $totalCartAmount + (int) bcmul($value['quantity'], $variation->price);
        }

        return $totalCartAmount;
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

        $totalCartAmount = $this->getCartAmount();

        $appliedDiscount = null;

        if ($this->discounts->count() !== 1) {
            $businessDiscounts = $this->discounts->sortByDesc('minimum_cart_amount');

            foreach ($businessDiscounts as $discount) {
                if ($totalCartAmount >= $discount->minimum_cart_amount) {
                    $appliedDiscount = $discount;

                    break;
                }
            }
        } else {
            $discount = $this->discounts->first();

            if ($totalCartAmount >= $discount->minimum_cart_amount) {
                $appliedDiscount = $this->discounts->first();
            }
        }

        if ($appliedDiscount === null) {
            return [
                'status' => 'failed',
                'message' => 'no have discounts applied'
            ];
        }

        if ($appliedDiscount->business_id !== $this->business->getKey()) {
            throw new \Exception("Invalid discount from business ID $this->business->getKey()");
        }

        $this->setDiscount($appliedDiscount);

        if ($this->discount->fixed_amount) {
            $discountAmount = $this->discount->fixed_amount;
        } else if ($this->discount->percentage) {
            $discountAmount = $totalCartAmount * $this->discount->percentage;
        } else {
            throw new \Exception("Undefined discount amount type");
        }

        return [
            'status' => 'success',
            'message' => 'discount applied',
            'discount_information' => [
                'discount_applied' => $this->discount,
                'discount_amount' => $discountAmount,
            ]
        ];
    }
}
