<?php

namespace App\Http\Requests;

use App\Business;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\Business\RecurringCycle;
use App\Manager\BusinessManagerInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(type="object")
 */
class SubscriptionPlanRequest extends FormRequest
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
     * @OA\Property(property="name"                     , type="string")
     * @OA\Property(property="currency"                 , type="string", nullable="true")
     * @OA\Property(property="amount"                   , type="number", format="double")
     * @OA\Property(property="reference"                , type="string", nullable="true")
     * @OA\Property(property="cycle"                    , type="string")
     * @OA\Property(property="description"              , type="string", nullable="true")
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'                      => 'required|string|max:255',
            'currency'                  => 'nullable|max:3',
            'amount'                    => 'required|numeric|decimal:0,2|min:1',
            'reference'                 => 'string|nullable|max:255',
            'cycle'                     => ['required',Rule::in(RecurringCycle::listConstants())],
            'description'               => 'string|nullable',
        ];
    }
}
