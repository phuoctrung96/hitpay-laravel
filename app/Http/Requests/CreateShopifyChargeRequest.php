<?php

namespace App\Http\Requests;

use App\Business;
use Illuminate\Foundation\Http\FormRequest;

class CreateShopifyChargeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'x_reference' => [
                'required',
                'string'
            ],
            'x_account_id' => [
                'required',
                'string'
            ],
            'x_amount' => [
                'required'
            ],
            'x_currency' => [
                'required',
                'string',
            ],
            'x_shop_country' => [
                'required',
                'string',
            ],
            'x_shop_name' => [
                'required',
                'string',
            ],
            'x_signature' => [
                'required',
                'string',
            ],
            'x_test' => [
                'required',
            ],
            'x_url_callback' => [
                'required',
                'string',
            ],
            'x_url_cancel' => [
                'required',
                'string',
            ],
            'x_url_complete' => [
                'required',
                'string',
            ],
            /*'x_transaction_type' => [
                'required',
                'string',
            ],*/
            'x_customer_billing_address1' => [
                'nullable',
            ],
            'x_customer_billing_address2' => [
                'nullable',
            ],
            'x_customer_billing_city' => [
                'nullable',
            ],
            'x_customer_billing_company' => [
                'nullable',
            ],
            'x_customer_billing_country' => [
                'nullable',
            ],
            'x_customer_billing_phone' => [
                'nullable',
            ],
            'x_customer_billing_state' => [
                'nullable',
            ],
            'x_customer_billing_zip' => [
                'nullable',
            ],
            'x_customer_email' => [
                'nullable',
                'string',
                'email',
                'max:255'
            ],
            'x_customer_first_name' => [
                'nullable',
                'string',
            ],
            'x_customer_last_name' => [
                'nullable',
                'string',
            ],
            'x_customer_phone' => [
                'nullable',
            ],
            'x_customer_shipping_address1' => [
                'nullable',
                'string',
            ],
            'x_customer_shipping_address2' => [
                'nullable',
                'string',
            ],
            'x_customer_shipping_city' => [
                'nullable',
                'string',
            ],
            'x_customer_shipping_country' => [
                'nullable',
                'string',
            ],
            'x_customer_shipping_first_name' => [
                'nullable',
                'string',
            ],
            'x_customer_shipping_last_name' => [
                'nullable',
                'string',
            ],
            'x_customer_shipping_zip' => [
                'nullable',
                'string',
            ],
            'x_description' => [
                'nullable',
                'string',
            ],
            'x_invoice' => [
                'nullable',
                'string',
            ]
        ];
    }
}
