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
            "cycle" => $this->cycle,
            "currency" => $this->currency,
            "price" => getReadableAmountByCurrency($this->currency, $this->price),
            "times_to_be_charged" => $this->times_to_be_charged,
            "times_charged" => $this->times_charged,
            "status" => $this->status,
            "send_email" => $this->send_email,
            "redirect_url" => $this->redirect_url,
            "payment_methods" => $this->payment_methods,
            "created_at" => (string) Carbon::parse($this->created_at)->format("Y-m-d\TH:i:s"),
            "updated_at" => (string) Carbon::parse($this->created_at)->format("Y-m-d\TH:i:s"),
            "expires_at" => (string) Carbon::parse($this->expires_at)->format("Y-m-d\TH:i:s"),
            "url" => route('recurring-plan.show', [
                'business_id' => $this->business_id,
                'recurring_plan_id' => $this->id,
            ])
        ];

        return $data;
    }
}
