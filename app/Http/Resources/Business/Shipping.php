<?php

namespace App\Http\Resources\Business;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Business\Shipping
 */
class Shipping extends JsonResource
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
        $data['id'] = $this->getKey();
        $data['calculation'] = $this->calculation;
        $data['name'] = $this->name;
        $data['description'] = $this->description;
        $data['rate'] = $this->rate;
        $data['formula'] = $this->formula;
        $data['is_active'] = $this->active;

        if ($this->relationLoaded('countries')) {
            foreach ($this->countries as $country) {
                $data['countries'][] = $country->country;
            }
        }

        $data['created_at'] = $this->created_at->toAtomString();
        $data['updated_at'] = $this->updated_at->toAtomString();

        return $data;
    }
}
