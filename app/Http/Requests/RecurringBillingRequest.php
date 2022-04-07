<?php

namespace App\Http\Requests;

use App\Business;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\Business\RecurringCycle;
use App\Manager\BusinessManagerInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(type="object")
 */
class RecurringBillingRequest extends FormRequest
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
     * @OA\Property(property="plan_id"                  , type="string")
     * @OA\Property(property="currency"                 , type="string", nullable="true")
     * @OA\Property(property="amount"                   , type="number", format="double", nullable="true")
     * @OA\Property(property="customer_email"           , type="string")
     * @OA\Property(property="customer_name"            , type="string", nullable="true")
     * @OA\Property(property="start_date"               , type="string", format="datetime")
     * @OA\Property(property="payment_methods"          , type="array", @OA\Items(type="string"), nullable="true")
     * @OA\Property(property="times_to_charge"          , type="number", format="double", nullable="true")
     * @OA\Property(property="redirect_url"             , type="string", nullable="true")
     * @OA\Property(property="send_email"               , type="boolean")
     *
     * @return array
     */
    public function rules()
    {
        return [
            'plan_id' => 'required|string',
            'currency' => 'nullable|max:3',
            'amount' => 'nullable|numeric|decimal:0,2|min:1',
            'customer_email' => 'required|email:rfc,dns',
            'customer_name' => 'nullable|string',
            'start_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'payment_methods' => ['nullable', 'array', Rule::in([PaymentMethodType::CARD, PaymentMethodType::GIRO])],
            'times_to_charge' => 'numeric|nullable',
            'redirect_url' => 'url|nullable',
            'send_email' => ['nullable', Rule::in(['true','false'])],
        ];
    }
}
