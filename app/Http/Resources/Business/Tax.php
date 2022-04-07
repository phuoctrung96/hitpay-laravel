<?php

namespace App\Http\Resources\Business;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Business\Tax
 */
class Tax extends JsonResource
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
            'name' => $this->name,
            'applies_overseas' => $this->applies_overseas,
            'applies_locally' => $this->applies_locally,
            'rate' => (float) bcmul((string) $this->rate, '100', 2),
        ];
    }
}
