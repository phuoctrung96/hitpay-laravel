<?php

namespace App\Http\Resources\Business;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Business\ProductVariation
 */
class ProductVariation extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|mixed
     * @throws \ReflectionException
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->getKey(),
            'stock_keeping_unit' => $this->stock_keeping_unit,
            'description' => $this->description,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'depth' => $this->depth,
            'variation_value_1' => $this->variation_value_1,
            'variation_value_2' => $this->variation_value_2,
            'variation_value_3' => $this->variation_value_3,
            'price' => getReadableAmountByCurrency($this->product->currency, $this->price),
            'price_display' => getFormattedAmount($this->product->currency, $this->price),
            'price_stored' => $this->price,
            'quantity' => $this->quantity,
            'quantity_alert_level' => $this->quantity_alert_level,
        ];

        return $data;
    }
}
