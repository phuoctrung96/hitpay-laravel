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
class Refund extends JsonResource
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
    public function toArray($refund)
    {
        $data = [
            "id" => $this->id,
            "payment_id" => $this->charge->id,
            "amount_refunded" => getReadableAmountByCurrency($this->charge->business->currency, $this->amount),
            "total_amount" => getReadableAmountByCurrency($this->charge->business->currency, $this->charge->amount),
            "currency" => $this->charge->currency,
            "status" => $this->status,
            "payment_method" => $this->charge->payment_provider_charge_method,
            "created_at" => (string) Carbon::parse($this->created_at)->format("Y-m-d\TH:i:s"),
        ];

        return $data;
    }
}
