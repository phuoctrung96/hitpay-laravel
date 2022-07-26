<?php

namespace App\Http\Resources\Business;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

/**
 * @OA\Schema(type="object")
 */
class RecurringBilling extends JsonResource
{
    /**
     * @OA\Property(property="id"                                   , type="string", format="uuid")
     * @OA\Property(property="business_recurring_plans_id"          , type="string", format="uuid")
     * @OA\Property(property="customer_name"                        , type="string")
     * @OA\Property(property="customer_email"                       , type="string")
     * @OA\Property(property="name"                                 , type="string")
     * @OA\Property(property="description"                          , type="string")
     * @OA\Property(property="currency"                             , type="string")
     * @OA\Property(property="price"                                , type="number", format="double")
     * @OA\Property(property="cycle"                                , type="string")
     * @OA\Property(property="times_to_be_charged"                  , type="number")
     * @OA\Property(property="times_charged"                        , type="number")
     * @OA\Property(property="status"                               , type="string")
     * @OA\Property(property="send_email"                           , type="boolean")
     * @OA\Property(property="redirect_url"                         , type="string")
     * @OA\Property(property="payment_methods"                      , type="array")
     * @OA\Property(property="updated_at"                           , type="string", format="date-time")
     * @OA\Property(property="created_at"                           , type="string", format="date-time")
     * @OA\Property(property="expires_at"                           , type="string", format="date-time")
     * @OA\Property(property="webhook"                              , type="string", nullable="true")
     * @OA\Property(property="save_card"                            , type="boolean", nullable="true")
     * @OA\Property(property="reference"                            , type="string", nullable="true")
     *
     * @return array
     */
    public function toArray($plan)
    {
        $data = [
            "id" => $this->id,
            "business_recurring_plans_id" => $this->business_recurring_plans_id,
            "customer_name" => $this->customer_name,
            "customer_email" => $this->customer_email,
            "name" => $this->name,
            "description" => $this->description,
            "reference" => $this->reference,
            "cycle" => $this->cycle,
            "currency" => $this->currency,
            "price" => getReadableAmountByCurrency($this->currency, $this->price),
            "times_to_be_charged" => $this->times_to_be_charged,
            "times_charged" => $this->times_charged,
            "status" => $this->status,
            "send_email" => $this->send_email,
            "save_card" => $this->save_card,
            "redirect_url" => $this->redirect_url,
            "payment_methods" => $this->payment_methods,
            "created_at" => (string) Carbon::parse($this->created_at)->format("Y-m-d\TH:i:s"),
            "updated_at" => (string) Carbon::parse($this->created_at)->format("Y-m-d\TH:i:s"),
            "expires_at" => (string) Carbon::parse($this->expires_at)->format("Y-m-d\TH:i:s"),
            "url" => route('recurring-plan.show', [
                'business_id' => $this->business_id,
                'recurring_plan_id' => $this->id,
            ]),
            'webhook' => $this->webhook,
        ];

        if ($this->payment_provider === \App\Enumerations\PaymentProvider::STRIPE_SINGAPORE && $this->save_card && $this->data){
            $data['payment_method']['card']['brand'] = $this->data['stripe']['payment_method']['card']['brand'];
            $data['payment_method']['card']['last4'] =$this->data['stripe']['payment_method']['card']['last4'];
        }

        return $data;
    }
}
