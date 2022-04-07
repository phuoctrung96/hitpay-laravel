<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\Shipping;
use App\Enumerations\AllCountryCode;
use App\Enumerations\Business\ShippingCalculation;
use App\Enumerations\CountryCode;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ShippingRepository
{
    /**
     * Create a new shipping.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Business\Shipping
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function store(Request $request, Business $business) : Shipping
    {
        $data = Validator::validate($request->all(), [
            'calculation' => [
                'required',
                Rule::in(ShippingCalculation::listConstants()),
            ],
            'name' => [
                'required',
                'string',
                'max:64',
            ],
            'description' => [
                'nullable',
                'string',
                'max:65536',
            ],
            'active' => [
                'required',
                'bool',
            ],
            'slots' => [
                'nullable',
            ],
            'rate' => [
                'required',
                'decimal:0,2', // TODO - Update to accept numeric? We assume SGD and MYR only
                'min:0',
            ],
            'country' => [
                'required_without:countries',
                Rule::in(static::getCountriesList()),
            ],
            'countries' => [
                'required_without:country',
                'array',
            ],
            'countries.*' => [
                'required_with:countries',
                Rule::in(static::getCountriesList()),
            ],
        ]);

        $data['rate'] = getRealAmountForCurrency($business->currency, $data['rate']);

        return DB::transaction(function () use ($business, $data) : Shipping {
            if (isset($data['country'])) {
                $countries = [
                    Arr::pull($data, 'country'),
                ];
            } else {
                $countries = Arr::pull($data, 'countries', []);
            }

            $shipping = $business->shippings()->create($data);

            $shipping->setCountries($countries);
            $shipping->load('countries');

            return $shipping;
        }, 3);
    }

    /**
     * Update an existing shipping.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business\Shipping $shipping
     *
     * @return \App\Business\Shipping
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function update(Request $request, Business $business, Shipping $shipping) : Shipping
    {
        $data = Validator::validate($request->all(), [
            'calculation' => [
                'required',
                Rule::in(ShippingCalculation::listConstants()),
            ],
            'name' => [
                'required',
                'string',
                'max:64',
            ],
            'description' => [
                'nullable',
                'string',
                'max:65536',
            ],
            'active' => [
                'required',
                'bool',
            ],
            'slots' => [
                'nullable',
            ],
            'rate' => [
                'required',
                'decimal:0,2', // TODO - Update to accept numeric? We assume SGD and MYR only
                'min:0',
            ],
            'country' => [
                'required_without:countries',
                Rule::in(static::getCountriesList()),
            ],
            'countries' => [
                'required_without:country',
                'array',
            ],
            'countries.*' => [
                'required_with:countries',
                Rule::in(static::getCountriesList()),
            ],
        ]);

        $data['rate'] = getRealAmountForCurrency($business->currency, $data['rate']);

        $shipping = DB::transaction(function () use ($shipping, $data) : Shipping {
            if (isset($data['country'])) {
                $countries = [
                    Arr::pull($data, 'country'),
                ];
            } else {
                $countries = Arr::pull($data, 'countries', []);
            }

            $shipping->update($data);
            $shipping->setCountries($countries);
            $shipping->load('countries');

            return $shipping;
        }, 3);

        return $shipping;
    }

    /**
     * Delete an existing shipping.
     *
     * @param \App\Business\Shipping $shipping
     *
     * @return bool|null
     * @throws \Throwable
     */
    public static function delete(Shipping $shipping) : ?bool
    {
        return DB::transaction(function () use ($shipping): ?bool {
            return $shipping->delete();
        }, 3);
    }

    /**
     * Get the list of codes for all countries, include global.
     *
     * @return array
     * @throws \ReflectionException
     */
    private static function getCountriesList()
    {
        return array_merge(['GLOBAL' => 'global'], AllCountryCode::listConstants());
    }
}
