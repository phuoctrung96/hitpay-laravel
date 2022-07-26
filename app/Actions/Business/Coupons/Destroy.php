<?php

namespace App\Actions\Business\Coupons;

use App\Business\Promotion;
use App\Enumerations\Business\PromotionAppliesToType;

class Destroy extends Action
{
    /**
     * @return bool
     * @throws \Exception
     */
    public function process() : bool
    {
        if ($this->coupon === null) {
            throw new \Exception("Coupon not found");
        }

        if ($this->coupon->business->getKey() !== $this->business->getKey()) {
            throw new \Exception("Coupon $this->coupon->getKey() not relate with business ID $this->business->getKey()");
        }

        if ($this->coupon->discount_type !== PromotionAppliesToType::ALL_PRODUCT) {
            Promotion::where('promotion_id', $this->coupon->getKey())->delete();
        }

        $this->coupon->delete();

        return true;
    }
}
