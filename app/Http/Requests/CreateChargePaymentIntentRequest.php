<?php

namespace App\Http\Requests;

use App\Business;
use App\Enumerations\Business\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;

class CreateChargePaymentIntentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
        /*$business       = Business::find($this->route('business_id'))->first();
        $paymentMethods = $business->getProviderMethods($business);

        return in_array($this->input('method'), $paymentMethods)? true: false;*/
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'method'    => 'required',
            'email'     => [
                'required',
                'string',
                'email',
                'max:255'
            ],
            'description' => 'nullable'
        ];
    }
}
