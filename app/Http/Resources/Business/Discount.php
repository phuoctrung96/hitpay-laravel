<?php

namespace App\Http\Resources\Business;

use App\Enumerations\Business\PromotionAppliesToType;
use Illuminate\Http\Resources\Json\JsonResource;

class Discount extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     * @throws \ReflectionException
     */
    public function toArray($request): array
    {
        $data = parent::toArray($request);

        $data['applies_to_ids'] = $this->applies_to_ids;

        $data['applies_to_type_name'] = PromotionAppliesToType::displayName($this->discount_type);

        return $data;
    }
}
