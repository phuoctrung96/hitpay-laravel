<?php

namespace App\Http\Resources\Business;

use Illuminate\Http\Resources\Json\JsonResource;

class EmailTemplate extends JsonResource
{
    /**
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->getKey(),
            'payment_receipt_template' => $this->payment_receipt_template,
            'order_confirmation_template' => $this->order_confirmation_template,
            'recurring_invoice_template' => $this->recurring_invoice_template,
            'invoice_receipt_template' => $this->invoice_receipt_template,
            'mobile_printer_template' => $this->mobile_printer_template,
            'business' => $this->business,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
