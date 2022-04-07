<?php

namespace App\Http\Resources\Business;

use App\Business\Charge;
use App\Http\Resources\Business;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\Business\ChargeStatus;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

/**
 * @OA\Schema(type="object")
 */
class PaymentRequestResource extends JsonResource
{
    /**
     * @OA\Property(property="id"                       , type="string", format="uuid")
     * @OA\Property(property="name"                     , type="string")
     * @OA\Property(property="email"                    , type="string")
     * @OA\Property(property="phone"                    , type="string")
     * @OA\Property(property="currency"                 , type="string")
     * @OA\Property(property="status"                   , type="string")
     * @OA\Property(property="purpose"                  , type="string", nullable="true")
     * @OA\Property(property="reference_number"         , type="string", nullable="true")
     * @OA\Property(property="payment_methods"          , type="array",
     *      @OA\Items(type="string")
     * )
     * @OA\Property(property="amount"                   , type="number", format="double", nullable="true")
     * @OA\Property(property="url"                      , type="string")
     * @OA\Property(property="redirect_url"             , type="string", nullable="true")
     * @OA\Property(property="webhook"                  , type="string", nullable="true")
     * @OA\Property(property="send_sms"                 , type="boolean")
     * @OA\Property(property="send_email"               , type="boolean")
     * @OA\Property(property="sms_status"               , type="string")
     * @OA\Property(property="email_status"             , type="string")
     * @OA\Property(property="allow_repeated_payments"  , type="boolean")
     * @OA\Property(property="expiry_date"              , type="string", format="date-time")
     * @OA\Property(property="payments"                 , type="array",
     *      @OA\Items(type="string")
     * )
     * @OA\Property(property="created_at"               , type="string", format="date-time")
     * @OA\Property(property="updated_at"               , type="string", format="date-time")
     *
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id'                        => $this->getKey(),
            'name'                      => $this->name,
            'email'                     => $this->email,
            'phone'                     => $this->phone,
            'amount'                    => number_format($this->amount, 2),
            'currency'                  => $this->currency,
            'status'                    => $this->current_status,
            'purpose'                   => $this->purpose,
            'reference_number'          => $this->reference_number,
            'payment_methods'           => $this->payment_methods,
            'url'                       => $this->url,
            'redirect_url'              => $this->redirect_url,
            'webhook'                   => $this->webhook,
            'send_sms'                  => $this->send_sms? true: false,
            'send_email'                => $this->send_email? true: false,
            'sms_status'                => $this->sms_status,
            'email_status'              => $this->email_status,
            'allow_repeated_payments'   => $this->allow_repeated_payments? true: false,
            'expiry_date'               => empty($this->expiry_date)? null: (string) Carbon::parse($this->expiry_date)->format("Y-m-d\TH:i:s"),
            //'business_id'               => $this->business_id,
            //'business'                  => new Business($this->business),
            'created_at'                => (string) Carbon::parse($this->created_at)->format("Y-m-d\TH:i:s"),
            'updated_at'                => (string) Carbon::parse($this->updated_at)->format("Y-m-d\TH:i:s"),
        ];

        if (count($payments = $this->getPaymentsResource())) {
            $data['payments'] = $payments;
        }

        return $data;
    }

    private function getPaymentsResource()
    {
        $payments   = [];

        foreach ($this->getPayments() as $charge) {
            $payments[] = new PaymentRequestPaymentResource($charge);
        }

        return $payments;
    }
}
