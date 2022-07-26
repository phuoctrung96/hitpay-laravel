<?php

namespace App\Actions\Business\Coupons;

use App\Business\Coupon;
use App\Business\Promotion;
use App\Enumerations\Business\PromotionAppliesToType;
use App\Enumerations\Business\PromotionType;
use Illuminate\Support\Facades;

class Update extends Action
{
    /**
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function process() : Coupon
    {
        if ($this->coupon === null) {
            throw new \Exception("Coupon not found");
        }

        if ($this->coupon->business->getKey() !== $this->business->getKey()) {
            throw new \Exception("Coupon $this->coupon->getKey() not relate with business ID $this->business->getKey()");
        }

        $rules = [
            'name' => 'required|string|max:255',
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

        if ($this->coupon->code === $this->data['code']) {
            $rules['code'] = 'required|string|max:255';
        } else {
            $rules['code'] = 'required|string|max:255|unique:business_coupons,code';
        }

        Facades\Validator::make($this->data, $rules)->validate();

        $this->data['fixed_amount'] = getRealAmountForCurrency($this->business->currency, $this->data['fixed_amount']);

        if ($this->data['is_promo_banner']) {
            $this->business->discounts()->update(['is_promo_banner' => false]);

            $this->business->coupons()
                ->where('id', '!=', $this->coupon->getKey())
                ->update(['is_promo_banner' => false]);
        }

        if (!isset($this->data['coupons_left'])) {
            $this->data['coupons_left'] = null;
        }

        $this->coupon->name = $this->data['name'];
        $this->coupon->code = $this->data['code'];
        $this->coupon->fixed_amount = $this->data['fixed_amount'];
        $this->coupon->percentage = $this->data['percentage'];
        $this->coupon->is_promo_banner = $this->data['is_promo_banner'];
        $this->coupon->banner_text = $this->data['banner_text'];
        $this->coupon->coupons_left = $this->data['coupons_left'];
        $this->coupon->coupon_type = $this->data['coupon_type'];
        $this->coupon->save();

        if ($this->coupon->discount_type !== PromotionAppliesToType::ALL_PRODUCT) {
            Promotion::where('promotion_id', $this->coupon->getKey())->delete();

            foreach ($this->data['applies_to_ids'] as $appliesToId) {
                $businessPromotion = new Promotion();
                $businessPromotion->promotion_type = PromotionType::COUPON;
                $businessPromotion->promotion_id = $this->coupon->getKey();
                $businessPromotion->applies_to_type = $this->coupon->coupon_type;
                $businessPromotion->applies_to_id = $appliesToId;

                $businessPromotion->save();
            }
        }

        return $this->coupon->refresh();
    }
}
