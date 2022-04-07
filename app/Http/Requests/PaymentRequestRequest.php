<?php

namespace App\Http\Requests;

use App\Business\PaymentRequest;
use App\Business;
use App\Enumerations\Business\PluginProvider;
use App\Manager\ApiKeyManager;
use App\Manager\BusinessManagerInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(type="object")
 */
class PaymentRequestRequest extends FormRequest
{
    private $businessManager;

    public function __construct(BusinessManagerInterface $businessManager)
    {
        $this->businessManager = $businessManager;

        parent::__construct();
    }

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
     * @OA\Property(property="email"                    , type="string")
     * @OA\Property(property="currency"                 , type="string")
     * @OA\Property(property="payment_methods"          , type="array", @OA\Items(type="string"))
     * @OA\Property(property="purpose"                  , type="string", nullable="true")
     * @OA\Property(property="reference_number"         , type="string", nullable="true")
     * @OA\Property(property="redirect_url"             , type="string", nullable="true")
     * @OA\Property(property="webhook"                  , type="string", nullable="true")
     * @OA\Property(property="amount"                   , type="number", format="double", nullable="true")
     * @OA\Property(property="send_sms"                 , type="boolean")
     * @OA\Property(property="send_email"               , type="boolean")
     * @OA\Property(property="allow_repeated_payments"  , type="boolean")
     * @OA\Property(property="expiry_date"              , type="string", format="datetime")
     * @OA\Property(property="channel"                  , type="string")
     * @OA\Property(property="business_id"              , type="string")
     *
     * @return array
     */
    public function rules()
    {
        if ($this->headers->has('X-BUSINESS-API-KEY')) {
            $business = $this->user()->businessesOwned()->first();
        }
        else {
            $business = Business::findOrFail($this->business_id);
        }
        $apiKey         = $business->apiKeys()->first();
        $paymentMethods = $this->businessManager->getByBusinessAvailablePaymentMethods($apiKey->business, $this->input('currency'), true);

        return [
            'name'                      => 'nullable',
            'email'                     => 'email|nullable',
            'phone'                     => 'string|nullable|between:0,15',
            'amount'                    => 'required|numeric|between:0.3,9999999.99',
            'currency'                  => 'required|max:3',
            'payment_methods'           => ['nullable', 'array', Rule::in(array_keys($paymentMethods))],
            'purpose'                   => 'sometimes|nullable',
            'redirect_url'              => 'url|nullable|between:1,1028',
            'webhook'                   => 'url|nullable|between:1,1028',
            'failed_webhook'             => 'url|nullable',
            'reference_number'          => 'sometimes|nullable',
            'send_sms'                  => ['nullable', Rule::in(['true','false'])],
            'send_email'                => ['nullable', Rule::in(['true','false'])],
            'allow_repeated_payments'   => ['nullable', Rule::in(['true','false'])],
            'expiry_date'               => 'nullable|date_format:Y-m-d H:i:s|after:now',
            'channel'                   => ['nullable', Rule::in(PluginProvider::CHANNELS)],
            'business_id'               => 'nullable'
        ];
    }
}
