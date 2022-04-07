<?php

namespace App\Http\Resources\Business;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

/**
 * @OA\Schema(type="object")
 */
class SubscriptionPlan extends JsonResource
{
    /**
     * @OA\Property(property="id"                       , type="string", format="uuid")
     * @OA\Property(property="name"                     , type="string")
     * @OA\Property(property="description"              , type="string")
     * @OA\Property(property="cycle"                    , type="string")
     * @OA\Property(property="currency"                 , type="string")
     * @OA\Property(property="price"                    , type="number", format="double", nullable="true")
     * @OA\Property(property="reference"                , type="string")
     * @OA\Property(property="updated_at"               , type="string", format="date-time")
     * @OA\Property(property="created_at"               , type="string", format="date-time")
     *
     * @return array
     */
    public function toArray($plan)
    {
        $data = [
            "id" => $this->id,
            "name" => $this->name,
            "description" => $this->description,
            "cycle" => $this->cycle,
            "currency" => $this->currency,
            "price" => getReadableAmountByCurrency($this->currency, $this->price),
            "reference" => $this->reference,
            "created_at" => (string) Carbon::parse($this->created_at)->format("Y-m-d\TH:i:s"),
            "updated_at" => (string) Carbon::parse($this->created_at)->format("Y-m-d\TH:i:s"),
        ];

        return $data;
    }
}
