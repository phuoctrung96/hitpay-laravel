<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\Discount;
use App\Enumerations\Business\PromotionAppliesToType;
use App\Enumerations\Business\PromotionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DiscountRepository
{
    /**
     * Create a new discount.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Business\Discount
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function store(Request $request, Business $business) : Discount
    {
        $requestData = Validator::validate($request->all(), [
            'name' => 'required|string|max:255',
            'fixed_amount' => 'required|numeric|between:0,999999.99',
            'percentage' => 'required|numeric|between:0,99.99',
            'minimum_cart_amount' => 'required|numeric|between:0,999999.99',
            'is_promo_banner' => 'required|bool',
            'banner_text' => 'nullable|string|max:1000',
            'discount_type' => 'required|numeric|in:1,2,3',
            'applies_to_ids' => 'required_unless:discount_type,1'
        ]);

        return DB::transaction(function () use ($business, $requestData) : Discount {
            $requestData['minimum_cart_amount'] = getRealAmountForCurrency($business->currency, $requestData['minimum_cart_amount']);
            $requestData['fixed_amount'] = getRealAmountForCurrency($business->currency, $requestData['fixed_amount']);

            if ($requestData['is_promo_banner']) {
                $business->coupons()->update(['is_promo_banner' => false]);
                $business->discounts()->update(['is_promo_banner' => false]);
            }

            $discount = new Discount();
            $discount->business_id = $business->getKey();
            $discount->is_promo_banner = $requestData['is_promo_banner'];
            $discount->name = $requestData['name'];
            $discount->percentage = $requestData['percentage'];
            $discount->fixed_amount = $requestData['fixed_amount'];
            $discount->banner_text = $requestData['banner_text'];
            $discount->discount_type = $requestData['discount_type'];
            $discount->minimum_cart_amount = $requestData['minimum_cart_amount'];

            $discount->save();

            if ($discount->discount_type !== PromotionAppliesToType::ALL_PRODUCT) {
                foreach ($requestData['applies_to_ids'] as $appliesToId) {
                    $businessPromotion = new Business\Promotion();
                    $businessPromotion->promotion_type = PromotionType::DISCOUNT;
                    $businessPromotion->promotion_id = $discount->getKey();
                    $businessPromotion->applies_to_type = $discount->discount_type;
                    $businessPromotion->applies_to_id = $appliesToId;

                    $businessPromotion->save();
                }
            }

            return $discount;
        }, 3);
    }

    /**
     * Update an existing discount.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business\Discount $discount
     *
     * @return \App\Business\Discount
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function update(Request $request, Discount $discount) : Discount
    {
        $requestData = Validator::validate($request->all(), [
            'name' => 'required|string|max:255',
            'fixed_amount' => 'required|numeric|between:0,999999.99',
            'percentage' => 'required|numeric|between:0,99.99',
            'minimum_cart_amount' => 'required|numeric|between:0,999999.99',
            'is_promo_banner' => 'required|bool',
            'banner_text' => 'nullable|string|max:1000',
            'discount_type' => 'required|numeric|in:1,2,3',
            'applies_to_ids' => 'required_unless:discount_type,1'
        ]);

        return DB::transaction(function () use ($discount, $requestData) : Discount {
            $business = $discount->business;

            $requestData['minimum_cart_amount'] = getRealAmountForCurrency($business->currency, $requestData['minimum_cart_amount']);
            $requestData['fixed_amount'] = getRealAmountForCurrency($business->currency, $requestData['fixed_amount']);

            if ($requestData['is_promo_banner']) {
                $business->coupons()->update(['is_promo_banner' => false]);

                $business->discounts()->where('id', '<>', $discount->getKey())
                    ->update(['is_promo_banner' => false]);
            }

            $discount->is_promo_banner = $requestData['is_promo_banner'];
            $discount->name = $requestData['name'];
            $discount->percentage = $requestData['percentage'];
            $discount->fixed_amount = $requestData['fixed_amount'];
            $discount->banner_text = $requestData['banner_text'];
            $discount->discount_type = $requestData['discount_type'];
            $discount->minimum_cart_amount = $requestData['minimum_cart_amount'];

            $discount->save();

            if ($discount->discount_type !== PromotionAppliesToType::ALL_PRODUCT) {
                Business\Promotion::where('promotion_id', $discount->getKey())->delete();

                foreach ($requestData['applies_to_ids'] as $appliesToId) {
                    $businessPromotion = new Business\Promotion();
                    $businessPromotion->promotion_type = PromotionType::DISCOUNT;
                    $businessPromotion->promotion_id = $discount->getKey();
                    $businessPromotion->applies_to_type = $discount->discount_type;
                    $businessPromotion->applies_to_id = $appliesToId;

                    $businessPromotion->save();
                }
            }

            return $discount->refresh();
        }, 3);
    }

    /**
     * Delete an existing discount.
     *
     * @param \App\Business\Discount $discount
     *
     * @return bool|null
     * @throws \Throwable
     */
    public static function delete(Discount $discount) : ?bool
    {
        return DB::transaction(function () use ($discount) : ?bool {
            if ($discount->discount_type !== PromotionAppliesToType::ALL_PRODUCT) {
                Business\Promotion::where('promotion_id', $discount->getKey())->delete();
            }

            return $discount->delete();
        }, 3);
    }
}
