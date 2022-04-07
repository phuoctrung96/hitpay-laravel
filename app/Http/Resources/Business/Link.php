<?php

namespace App\Http\Resources\Business;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class Link
 * @package App\Http\Resources\Business
 */
class Link extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|mixed
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function toArray($request)
    {
        $data['payment_link'] = $this->url;

        $data['created_at'] = $this->created_at->toAtomString();
        $data['updated_at'] = $this->updated_at->toAtomString();

        return $data;
    }
}
