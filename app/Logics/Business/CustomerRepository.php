<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\Customer;
use App\Enumerations\Gender;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerRepository
{
    /**
     * Create a new customer.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param bool $addressInArray
     *
     * @return \App\Business\Customer
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function store(Request $request, Business $business, bool $addressInArray = true) : Customer
    {
        if ($addressInArray) {
            $rules = [
                'address' => [
                    'nullable',
                    'array',
                ],
                'address.street' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'address.city' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'address.state' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'address.postal_code' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'address.country' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ];
        } else {

            $rules = [
                'street' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'city' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'state' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'postal_code' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'country' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ];
        }

        $data = Validator::validate($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'birth_date' => [
                    'nullable',
                    'date_format:Y-m-d',
                ],
                'gender' => [
                    'nullable',
                    Rule::in(Gender::listConstants()),
                ],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('business_customers')->where('business_id', $business->getKey()),
                ],
                'phone_number' => [
                    'nullable',
                    'digits_between:8,15',
                ],
                'remark' => [
                    'nullable',
                    'string',
                    'max:65536',
                ],
            ] + $rules);

        $data += Arr::pull($data, 'address', []);

        if(!empty($data['country'])) {
            $data['country'] = $data['country'] == 'singapore'
                ? 'sg'
                : Str::substr($data['country'], 0, 2);
        }

        return DB::transaction(function () use ($business, $data) : Customer {
            return $business->customers()->create($data);
        }, 3);
    }

    /**
     * Update an existing customer.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business\Customer $customer
     * @param bool $addressInArray
     *
     * @return \App\Business\Customer
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function update(Request $request, Customer $customer, bool $addressInArray = true) : Customer
    {
        if ($addressInArray) {
            $rules = [
                'address' => [
                    'nullable',
                    'array',
                ],
                'address.street' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'address.city' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'address.state' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'address.postal_code' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'address.country' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ];
        } else {
            $rules = [
                'street' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'city' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'state' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'postal_code' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'country' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ];
        }

        $data = Validator::validate($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'birth_date' => [
                    'nullable',
                    'date_format:Y-m-d',
                ],
                'gender' => [
                    'nullable',
                    Rule::in(Gender::listConstants()),
                ],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('business_customers')->where('business_id', $customer->business_id)
                        ->ignore($customer->getKey()),
                ],
                'phone_number' => [
                    'nullable',
                    'digits_between:8,15',
                ],
                'remark' => [
                    'nullable',
                    'string',
                    'max:65536',
                ],
            ] + $rules);

        $data += Arr::pull($data, 'address', []);

        if(!empty($data['country'])) {
            $data['country'] = $data['country'] == 'singapore'
                ? 'sg'
                : Str::substr($data['country'], 0, 2);
        }

        $customer = DB::transaction(function () use ($customer, $data) : Customer {
            $customer->update($data);

            return $customer;
        }, 3);

        return $customer;
    }

    /**
     * Delete an existing customer.
     *
     * @param \App\Business\Customer $customer
     *
     * @return bool|null
     * @throws \Throwable
     */
    public static function delete(Customer $customer) : ?bool
    {
        return DB::transaction(function () use ($customer) : ?bool {
            return $customer->delete();
        }, 3);
    }

    /**
     * @param Business $business
     * @param string $email
     * @param string|null $name
     * @param string|null $phone
     *
     * @return Customer
     * @throws \Throwable
     */
    public static function createByEmail(Business $business, $email, $name = null, $phone = null) : Customer
    {
        $attributes = ['email' => $email];
        if(!empty($name)) {
            $attributes['name'] = $name;
        }
        if(!empty($phone)) {
            $attributes['phone_number'] = $phone;
        }

        return DB::transaction(function () use ($business, $attributes) : Customer {
            return $business->customers()->create($attributes);
        }, 3);
    }

    /**
     * @param Business $business
     * @param array $data
     *
     * @return Customer
     * @throws \Throwable
     */
    public static function create(Business $business, array $data) : Customer
    {
        return DB::transaction(function () use ($business, $email) : Customer {
            return $business->customers()->create([
                'email'         => $data['customer_email'],
                'phone_number'  => $data['customer_phone'],
                'remark'        => $data['description'],
                'address'       => [
                    'street'        => $data['customer_billing_address1'],
                    'city'          => $data['customer_billing_city'],
                    'state'         => $data['customer_billing_state']?? null,
                    'postal_code'   => $data['customer_billing_zip'],
                    'country'       => strtolower($data['customer_billing_country'])
                ]
            ]);
        }, 3);
    }

     /**
     * @param Business $business
     * @param string $email
     *
     * @return Customer
     * @throws \Throwable
     */
    public static function findByBusinessAndEmail(Business $business, $email) : ?Customer
    {
        return $business->customers()->where('email', $email)->first();
    }
}
