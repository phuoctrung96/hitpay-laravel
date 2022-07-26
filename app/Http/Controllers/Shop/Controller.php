<?php

namespace App\Http\Controllers\Shop;

use App\Business;
use App\Business\Product;
use App\Business\ProductVariation;
use App\Enumerations\AllCountryCode;
use App\Enumerations\CountryCode;
use App\Enumerations\PaymentProvider;
use App\Http\Controllers\Controller as Base;
use Closure;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Stripe\ApplePayDomain;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Stripe;

class Controller extends Base
{
    /**
     * Check is product available.
     *
     * @param \App\Business\Product $product
     * @param \App\Business $business
     * @param null $default
     *
     * @return \App\Business\Product|mixed|null
     */
    public function isProductAvailable(Product $product, $default = null)
    {
        if (!$product->isPublished()) {
            return $this->returnDefault($default);
        }

        $product->load('images', 'variations');

        if ($product->isManageable()) {
            if ($product->variations_count > 1) {
                $callback = function (ProductVariation $variation) {
                    if ($variation->quantity === null) {
                        return 1;
                    }

                    return $variation->quantity;
                };

                if ($product->variations->sum($callback) < 1) {
                    return $this->returnDefault($default);
                }
            } else {
                $variation = $product->variations->first();

                if (!$variation instanceof ProductVariation) {
                    return $this->returnDefault($default);
                } elseif ($variation->quantity < 1) {
                    return $this->returnDefault($default);
                }
            }
        }

        return true;
    }

    /**
     * Helper to return default value.
     *
     * @param \Closure|mixed|null $default
     *
     * @return mixed|null
     */
    public function returnDefault($default = null)
    {
        if ($default instanceof Closure) {
            return $default();
        }

        return $default;
    }

    /**
     * @param \App\Business $business
     *
     * @return array
     * @throws \ReflectionException
     */
    public function getCheckoutOptions(Business $business, $domain = null)
    {

        $countriesList = [];
        $shippingOptions = [];

        foreach ($business->shippings as $shipping) {
            if ($shipping->countries->count()) {
                foreach ($shipping->countries as $country) {
                    if (Lang::has('misc.country.'.$country->country)) {
                        $countryName = Lang::get('misc.country.'.$country->country);
                    } else {
                        $countryName = strtoupper($country->country);
                    }

                    $countriesList[$country->country] = [
                        'code' => $country->country,
                        'name' => $countryName,
                    ];

                    $shippingOptions[$country->country]['country'] = $country->country;
                    $shippingOptions[$country->country]['country_name'] = $countryName;
                    $shippingOptions[$country->country]['options'][] = [
                        'id' => $shipping->getKey(),
                        'name' => $shipping->name,
                        'calculation' => $shipping->calculation,
                        'calculation_name' => Lang::get('misc.shipping_calculation.'.$shipping->calculation),
                        'country' => $country->country,
                        'country_name' => $countryName,
                        'description' => $shipping->description,
                        'rate' => getReadableAmountByCurrency($business->currency, $shipping->rate),
                        'rate_display' => getFormattedAmount($business->currency, $shipping->rate),
                        'rate_stored' => $shipping->rate,
                        'slots' => json_decode($shipping->slots)
                    ];
                }
            } else {
                $global[] = [
                    'id' => $shipping->getKey(),
                    'name' => $shipping->name,
                    'calculation' => $shipping->calculation,
                    'calculation_name' => Lang::get('misc.shipping_calculation.'.$shipping->calculation),
                    'country' => 'global',
                    'country_name' => Lang::get('misc.global'),
                    'description' => $shipping->description,
                    'rate' => getReadableAmountByCurrency($business->currency, $shipping->rate),
                    'rate_display' => getFormattedAmount($business->currency, $shipping->rate),
                    'rate_stored' => $shipping->rate,
                    'slots' => json_decode($shipping->slots)
                ];
            }
        }

        foreach ($shippingOptions as $country => $shippingOption) {
            $shippingOptions[$country]['options'] = Collection::make($shippingOption['options'])
                ->sortBy('rate')->values()->toArray();
        }

        $shippingOptions = Collection::make($shippingOptions)->sortBy('country_name')->toArray();

        if (isset($global)) {
            $shippingOptions = array_merge([
                'global' => [
                    'country' => 'global',
                    'country_name' => 'Global',
                    'options' => Collection::make($global)->sortBy('rate')->values()->toArray(),
                ],
            ], $shippingOptions);

            foreach (AllCountryCode::listConstants() as $code) {
                if (Lang::has('misc.country.'.$code)) {
                    $countryName = Lang::get('misc.country.'.$code);
                } else {
                    $countryName = strtoupper($code);
                }

                $countriesList[$code] = [
                    'code' => $code,
                    'name' => $countryName,
                ];
            }
        }

        $countriesList = Collection::make($countriesList);
        $countriesList = $countriesList->sortBy('name');

        return [
            'countries_list' => $countriesList->toArray(),
            'shippings' => $shippingOptions,
            'stripe' => [
                'publishable_key' => Config::get('services.stripe.'.$business->country.'.key'),
            ],
        ];
    }

    /**
     * Get order discount.
     *
     * @param \App\Business\Discount $discounts[]
     * @param int $totalCartAmount
     *
     * @return array
     */
    public function getDiscount($discounts, $totalCartAmount){
        foreach ($discounts as $discount) {
            if ($totalCartAmount >= $discount->minimum_cart_amount){
                if ($discount->fixed_amount) $discountAmount = $discount->fixed_amount;
                if ($discount->percentage) $discountAmount = bcmul($totalCartAmount, $discount->percentage, 2);
                $appliedDiscount = $discount;
                break;
            }
        }
            return [
                'amount' => $discountAmount ?? 0,
                'name' => isset($appliedDiscount) ? $appliedDiscount['name'] : ''
            ];
    }
}
