<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(type="object")
 */
class RefundRequest extends FormRequest
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
     * @OA\Property(property="amount"                   , type="number", format="double", nullable="true")
     * @OA\Property(property="payment_id"              , type="string")
     *
     * @return array
     */
    public function rules()
    {

        return [
            'payment_id'                => 'required|exists:business_charges,id',
            'amount'                    => 'required|numeric|between:0.3,9999999.99',
        ];
    }
}
