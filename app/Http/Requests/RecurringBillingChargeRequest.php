<?php

namespace App\Http\Requests;

use App\Business;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\Business\RecurringCycle;
use App\Enumerations\Business\SupportedCurrencyCode;
use App\Enumerations\CurrencyCode;
use App\Manager\BusinessManagerInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(type="object")
 */
class RecurringBillingChargeRequest extends FormRequest
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

    protected function prepareForValidation()
    {
        if ($this->has('currency'))
            $this->merge(['currency'=>strtolower($this->currency)]);
    }

    /**
     * @OA\Property(property="currency"                 , type="string")
     * @OA\Property(property="amount"                   , type="number", format="double", nullable="true")
     *
     * @return array
     */
    public function rules()
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:1',
                'max:9999999'
            ],
            'currency' => ['required','max:3', Rule::in(array_values(SupportedCurrencyCode::listConstants()))],
        ];
    }
}
