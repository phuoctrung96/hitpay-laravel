<?php

namespace App\Http\Requests;

use App\Business;
use App\Business\GatewayProvider;
use Illuminate\Foundation\Http\FormRequest;

class GatewayProviderRequest extends FormRequest
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
        $id = $this->gateway_provider_id instanceof GatewayProvider? $this->gateway_provider_id->getKey(): null;
        return [
            'name' => [
                'required',
                "unique_name_and_business:{$this->route('business_id')->getKey()},".$id,
                'string'
            ],
            'methods' => [
                'required'
            ]
        ];
    }

    public function messages()
    {
        return [
            'name.unique_name_and_business' => 'Integration has already been setup. Edit the existing integration to make changes',
        ];
    }
}
