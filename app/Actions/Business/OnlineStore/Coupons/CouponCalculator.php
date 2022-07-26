<?php
namespace App\Actions\Business\OnlineStore\Coupons;

class CouponCalculator extends Action
{
    protected \App\Business\Coupon $coupon;

    /**
     * @return array
     * @throws \Exception
     */
    public function process(): array
    {
        if ($this->coupon->coupon_type === \App\Enumerations\Business\PromotionAppliesToType::ALL_PRODUCT) {
            return AppliesAllProduct::withBusiness($this->business)->setCart($this->cart)
                ->setCoupon($this->coupon)->process();
        } elseif ($this->coupon->coupon_type === \App\Enumerations\Business\PromotionAppliesToType::SPECIFIC_CATEGORIES) {
            return AppliesSpecificCategory::withBusiness($this->business)->setCart($this->cart)
                ->setCoupon($this->coupon)->process();
        } elseif ($this->coupon->coupon_type === \App\Enumerations\Business\PromotionAppliesToType::SPECIFIC_PRODUCTS) {
            return AppliesSpecificProduct::withBusiness($this->business)->setCart($this->cart)
                ->setCoupon($this->coupon)->process();
        } else {
            throw new \Exception("Undefined coupon type");
        }
    }
}
