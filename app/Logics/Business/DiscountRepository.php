<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\Discount;
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
        ]);

        return DB::transaction(function () use ($business, $requestData) : Discount {
            $requestData['minimum_cart_amount'] = getRealAmountForCurrency($business->currency, $requestData['minimum_cart_amount']);
            $requestData['fixed_amount'] = getRealAmountForCurrency($business->currency, $requestData['fixed_amount']);

            if ($requestData['is_promo_banner']) {
                $business->coupons()->update(['is_promo_banner' => false]);
                $business->discounts()->update(['is_promo_banner' => false]);
            }

            return $business->discounts()->create($requestData);
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
        ]);

        return DB::transaction(function () use ($discount, $requestData) : Discount {
            $business = $discount->business;

            $requestData['minimum_cart_amount'] = getRealAmountForCurrency($business->currency, $requestData['minimum_cart_amount']);
            $requestData['fixed_amount'] = getRealAmountForCurrency($business->currency, $requestData['fixed_amount']);

            if ($requestData['is_promo_banner']) {
                $business->coupons()->update(['is_promo_banner' => false]);
                $business->discounts()->update(['is_promo_banner' => false]);
            }

            $discount->update($requestData);

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
            return $discount->delete();
        }, 3);
    }
}
