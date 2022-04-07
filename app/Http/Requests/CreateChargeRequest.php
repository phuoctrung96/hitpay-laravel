<?php

namespace App\Http\Requests;

use App\Business;
use Illuminate\Foundation\Http\FormRequest;

class CreateChargeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $business = Business::find($this->route('business_id'))->first();

        return in_array($this->input('currency'), [$business->currency])? true: false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'currency' => [
                'required',
                'string'
            ],
            'amount' => [
                'required',
                'decimal:0,2',
            ],
            'remark' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
