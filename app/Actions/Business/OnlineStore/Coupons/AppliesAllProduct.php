<?php

namespace App\Actions\Business\OnlineStore\Coupons;

class AppliesAllProduct extends Action
{
    /**
     * @return array
     * @throws \Exception
     */
    public function process(): array
    {
        $this->validateCoupon();

        return [
            'status' => 'success',
            'message' => 'coupon applied',
            'coupon' => $this->coupon,
            'is_product_price_changed' => false,
        ];
    }
}
