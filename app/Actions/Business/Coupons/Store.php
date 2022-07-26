<?php

namespace App\Actions\Business\Coupons;

use App\Business\Coupon;
use App\Business\Promotion;
use App\Enumerations\Business\PromotionAppliesToType;
use App\Enumerations\Business\PromotionType;
use Illuminate\Support\Facades;

class Store extends Action
{
    /**
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     */
    public function process() : Coupon
    {
        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:business_coupons,code',
            'fixed_amount' => 'required|decimal:0,2',
            'percentage' => 'required|decimal:4,5',
            'is_promo_banner' => 'required|bool',
            'banner_text' => 'nullable|string|max:1000',
            'coupons_left' => 'nullable|int',
            'coupon_type' => [
                'required',
                'in:1,2,3'
            ],
            'applies_to_ids' => [
                'required_unless:coupon_type,1'
            ]
        ];

        Facades\Validator::make($this->data, $rules)->validate();

        $this->data['fixed_amount'] = getRealAmountForCurrency($this->business->currency, $this->data['fixed_amount']);

        if ($this->data['is_promo_banner']) {
            $this->business->discounts()->update(['is_promo_banner' => false]);

            $this->business->coupons()->update(['is_promo_banner' => false]);
        }

        if (!isset($this->data['coupons_left'])) {
            $this->data['coupons_left'] = null;
        }

        $businessCoupon = new Coupon();
        $businessCoupon->business_id = $this->business->getKey();
        $businessCoupon->name = $this->data['name'];
        $businessCoupon->code = $this->data['code'];
        $businessCoupon->fixed_amount = $this->data['fixed_amount'];
        $businessCoupon->percentage = $this->data['percentage'];
        $businessCoupon->is_promo_banner = $this->data['is_promo_banner'];
        $businessCoupon->banner_text = $this->data['banner_text'];
        $businessCoupon->coupons_left = $this->data['coupons_left'];
        $businessCoupon->coupon_type = $this->data['coupon_type'];
        $businessCoupon->save();

        if ($businessCoupon->coupon_type != PromotionAppliesToType::ALL_PRODUCT) {
            foreach ($this->data['applies_to_ids'] as $appliesToId) {
                $businessPromotion = new Promotion();
                $businessPromotion->promotion_type = PromotionType::COUPON;
                $businessPromotion->promotion_id = $businessCoupon->getKey();
                $businessPromotion->applies_to_type = $businessCoupon->coupon_type;
                $businessPromotion->applies_to_id = $appliesToId;

                $businessPromotion->save();
            }
        }

        return $businessCoupon;
    }
}
