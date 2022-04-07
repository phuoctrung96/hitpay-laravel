<?php

namespace App\Http\Resources\Business;

use App\Business\PaymentRequest;
use App\Http\Resources\Business;
use App\Enumerations\Business\PluginProvider;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

/**
 * @OA\Schema(type="object")
 */
class PaymentRequestPaymentResource extends JsonResource
{
    /**
     * @OA\Property(property="id"                       , type="string", format="uuid")
     * @OA\Property(property="quantity"                 , type="string")
     * @OA\Property(property="status"                   , type="string")
     * @OA\Property(property="buyer_name"               , type="string")
     * @OA\Property(property="buyer_phone"              , type="string")
     * @OA\Property(property="buyer_email"              , type="string")
     * @OA\Property(property="currency"                 , type="string")
     * @OA\Property(property="payment_type"             , type="string")
     * @OA\Property(property="amount"                   , type="number", format="double")
     * @OA\Property(property="refunded_amount"          , type="number", format="double")
     * @OA\Property(property="fees"                     , type="number", format="double")
     * @OA\Property(property="created_at"               , type="string", format="date-time")
     * @OA\Property(property="updated_at"               , type="string", format="date-time")
     *
     * @return array
     */
    public function toArray($request)
    {
        $paymentRequest = PaymentRequest::find($this->plugin_provider_reference);
        $customer       = $this->customer;

        return [
            'id'                        => $this->getKey(),
            'quantity'                  => 1,
            'status'                    => $this->status,
            'buyer_name'                => $paymentRequest->name,
            'buyer_phone'               => $paymentRequest->phone,
            'buyer_email'               => ($customer)? $customer->email: $paymentRequest->email,
            'currency'                  => $this->currency,
            'amount'                    => number_format(getReadableAmountByCurrency($this->currency, $this->amount), 2),
            'refunded_amount'           => number_format(getReadableAmountByCurrency($this->currency, $this->refunds()->where('is_cashback',0)->where('is_campaign_cashback',0)->sum('amount')), 2),
            'payment_type'              => $this->payment_provider_charge_method,
            'fees'                      => $this->getFormattedFees(),
            'created_at'                => (string) Carbon::parse($this->created_at)->format("Y-m-d\TH:i:s"),
            'updated_at'                => (string) Carbon::parse($this->updated_at)->format("Y-m-d\TH:i:s"),
        ];
    }
}
