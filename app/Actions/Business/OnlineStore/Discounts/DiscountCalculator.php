<?php
namespace App\Actions\Business\OnlineStore\Discounts;

use App\Business\ProductVariation;
use App\Enumerations\Business\PromotionAppliesToType;
use Illuminate\Support\Facades\Log;

class DiscountCalculator extends Action
{
    /**
     * @return int
     */
    private function getCartAmount(): int
    {
        if (!isset($this->cart['products'])) {
            Log::critical("no have products key on carts. please check.");

            return 0;
        }

        $totalCartAmount = 0;

        foreach ($this->cart['products'] as $key => $value) {
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
        $results = [];

        // check discount per product have?
        $discountsAppliesToSpecificProducts = $this->business->discounts()
            ->where('discount_type', PromotionAppliesToType::SPECIFIC_PRODUCTS)
            ->get();

        if ($discountsAppliesToSpecificProducts->count() > 0) {
            $appliesSpecificProduct = AppliesSpecificProduct::withBusiness($this->business)
                ->setDiscounts($discountsAppliesToSpecificProducts)
                ->setCart($this->cart)->process();

            if ($appliesSpecificProduct['status'] === 'success') {
                if (isset($appliesSpecificProduct['discount_information'])) {
                    foreach ($appliesSpecificProduct['discount_information'] as $discountInformation) {
                        $results[] = $discountInformation;
                    }
                }
            }
        }

        $discountsAppliesToSpecificCategories = $this->business->discounts()
            ->where('discount_type', PromotionAppliesToType::SPECIFIC_CATEGORIES)
            ->get();

        if ($discountsAppliesToSpecificCategories->count() > 0) {
            $appliesSpecificCategory = AppliesSpecificCategory::withBusiness($this->business)
                ->setDiscounts($discountsAppliesToSpecificCategories)
                ->setCart($this->cart)->process();

            if ($appliesSpecificCategory['status'] === 'success') {
                if (isset($appliesSpecificCategory['discount_information'])) {
                    foreach ($appliesSpecificCategory['discount_information'] as $discountInformation) {
                        $results[] = $discountInformation;
                    }
                }
            }
        }

        $discountsAppliesToAllProducts = $this->business->discounts()
            ->where('discount_type', PromotionAppliesToType::ALL_PRODUCT)
            ->get();

        if ($discountsAppliesToAllProducts->count() > 0) {
            $appliesAllProduct = AppliesAllProduct::withBusiness($this->business)
                ->setDiscounts($discountsAppliesToAllProducts)
                ->setCart($this->cart)->process();

            if ($appliesAllProduct['status'] === 'success') {
                $results[] = $appliesAllProduct['discount_information'];
            }
        }

        if (count($results) === 0) {
            return [
                'status' => 'failed',
                'message' => 'no have discount applies',
            ];
        }

        return [
            'status' => 'success',
            'message' => 'any discount applies',
            'discount_information' => $results
        ];
    }
}
