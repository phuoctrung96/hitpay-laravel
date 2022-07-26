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
     * @OA\Property(property="webhook"                  , type="string", nullable="true")
     * @OA\Property(property="save_card"                , type="boolean", nullable="true")
     * @OA\Property(property="reference"                , type="string", nullable="true")


     *
     * @return array
     */
    public function rules()
    {
        return [
            'plan_id' => 'required_without:save_card|required_if:save_card,false|string|nullable',
            'currency' => ['nullable','max:3', Rule::in(array_values(SupportedCurrencyCode::listConstants()))],
            'amount' => 'required_without:plan_id|nullable|numeric|decimal:0,2|min:1',
            'customer_email' => 'required|email:rfc,dns',
            'customer_name' => 'nullable|string',
            'start_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'payment_methods' => ['nullable', 'array', Rule::in([PaymentMethodType::CARD, PaymentMethodType::GIRO])],
            'times_to_be_charge' => 'numeric|nullable|between:1,1000',
            'redirect_url' => 'url|nullable',
            'send_email' => ['nullable', Rule::in(['true','false'])],
            'webhook' => 'url|nullable|between:1,1028',
            'save_card' => ['nullable', Rule::in(['true','false'])],
            'reference' => 'string|nullable',
            'expires_at' => [
                'nullable',
                'date_format:Y-m-d'
            ]
        ];
    }
}
