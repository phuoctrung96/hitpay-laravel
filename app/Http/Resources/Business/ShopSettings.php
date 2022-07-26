<?php

namespace App\Http\Resources\Business;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopSettings extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request): array
    {
        $enableDate = null;
        $enableTime = null;

        if ($this->enable_datetime) {
            $enableDate = Carbon::parse($this->enable_datetime)->format('Y-m-d');
            $enableTime = Carbon::parse($this->enable_datetime)->format('h:i A');
        }

        return [
            'id' => $this->getKey(),
            'shop_state' => $this->shop_state,
            'slots' => $this->slots,
            'can_pick_up' => $this->can_pick_up,
            'seller_notes' => $this->seller_notes,
            'enable_datetime' => $this->enable_datetime,
            'enable_date' => $enableDate,
            'enable_time' => $enableTime,
            'enabled_shipping' => $this->enabled_shipping,
            'get_started' => $this->get_started,
            'thank_message' => $this->thank_message,
            'is_redirect_order_completion' => $this->is_redirect_order_completion,
            'url_redirect_order_completion' => $this->url_redirect_order_completion,
            'url_facebook' => $this->url_facebook,
            'url_instagram' => $this->url_instagram,
            'url_twitter' => $this->url_twitter,
            'url_tiktok' => $this->url_tiktok,
        ];
    }
}
