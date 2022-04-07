<?php

namespace App\Http\Resources\Business;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Business
 */
class TaxDetail extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->getKey(),
            'business_name' => $this->name,
            'individual_name' => $this->individual_name,
            'tax_registration_number' => $this->tax_registration_number
        ];
    }
}
