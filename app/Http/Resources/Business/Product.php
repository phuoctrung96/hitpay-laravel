<?php

namespace App\Http\Resources\Business;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * @mixin \App\Business\Product
 */
class Product extends JsonResource
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
        $data['id'] = $this->getKey();
        $data['business_id'] = $this->business_id;
        $data['category_id'] = $this->business_product_category_id;

        if ($this->relationLoaded('category') && $this->category) {
            $data['category'] = new ProductCategory($this->category);
        }

        $data['name'] = $this->name;
        $data['headline'] = $this->headline;
        $data['description'] = $this->description;
        $data['variation_key_1'] = $this->variation_key_1;
        $data['variation_key_2'] = $this->variation_key_2;
        $data['variation_key_3'] = $this->variation_key_3;
        $data['currency'] = $this->currency;
        $data['price'] = getReadableAmountByCurrency($this->currency, $this->price);
        $data['price_display'] = getFormattedAmount($this->currency, $this->price);
        $data['price_stored'] = $this->price;
        $data['tax_id'] = $this->business_tax_id;
        $data['is_manageable'] = $this->isManageable();
        $data['is_pinned'] = $this->is_pinned;
        $data['status'] = $this->status;
        $data['has_variations'] = $this->hasVariations();
        $data['is_shopify'] = $this->isShopify();

        $data['product_url'] = $this->shortcut_id
            ? URL::route('shortcut', $this->shortcut_id)
            : URL::route('shop.product', [
                $this->business_id,
                $this->getKey(),
            ]);

        if ($this->relationLoaded('tax') && $this->tax) {
            $data['tax'] = new Tax($this->tax);
        }

        $data['variations_count'] = $this->variations_count;

        if ($this->relationLoaded('variations')) {
            foreach ($this->variations as $variation) {
                $data['variations'][] = [
                    'id' => $variation->getKey(),
                    'stock_keeping_unit' => $variation->stock_keeping_unit,
                    'description' => $variation->description,
                    'weight' => $variation->weight,
                    'length' => $variation->length,
                    'width' => $variation->width,
                    'depth' => $variation->depth,
                    'variation_value_1' => $variation->variation_value_1,
                    'variation_value_2' => $variation->variation_value_2,
                    'variation_value_3' => $variation->variation_value_3,
                    'price' => getReadableAmountByCurrency($this->currency, $variation->price),
                    'price_display' => getFormattedAmount($this->currency, $variation->price),
                    'price_stored' => $variation->price,
                    'quantity' => $variation->quantity,
                    'quantity_alert_level' => $variation->quantity_alert_level,
                ];
            }
        }

        if ($this->shopify_id) {
            $data['shopify'] = [
                'id' => $this->shopify_id,
                'inventory_item_id' => $this->shopify_inventory_item_id,
                'sku' => $this->shopify_stock_keeping_unit,
                'image_url' => $this->display('image'),
            ];
        } elseif ($this->relationLoaded('images')) {
            $data['images'] = Image::collection($this->images)->toArray($request);
        }

        $data['is_published'] = $this->is_published;
        $data['created_at'] = $this->created_at->toAtomString();
        $data['updated_at'] = $this->updated_at->toAtomString();
        $data['starts_at'] = optional($this->starts_at)->toAtomString();
        $data['ends_at'] = optional($this->ends_at)->toAtomString();

        return $data;
    }
}
