<?php

namespace App\Http\Requests;

use HitPay\Stripe\Core;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterPartnerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return !auth()->user();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'display_name' => [
                'required',
                'string',
                'max:255',
            ],
            'website' => [
                'required',
                'regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/',
                'max:255',
            ],
            'referred_channel' => [
                'required',
            ],
            'merchant_category' => [
                'required',
                'string'
            ],
            'services' => [
                'required',
                'string',
            ],
            'other_service' => [
                'sometimes',
            ],
            'short_description' => [
                'required',
                'string',
            ],
            'special_offer' => [
                'nullable',
                'string',
            ],
            'platforms' => [
                'nullable',
                'string',
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
            ],
            'country' => [
                'required',
                Rule::in(array_keys(Core::getCountries())),
            ],
        ];
    }
}
