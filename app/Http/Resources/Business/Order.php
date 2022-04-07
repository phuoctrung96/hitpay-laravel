<?php

namespace App\Http\Resources\Business;

use App\Enumerations\Business\Channel;
use App\Enumerations\Business\OrderStatus;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * @mixin \App\Business\Order
 */
class Order extends JsonResource
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
        if ($this->status === OrderStatus::DRAFT) {
            $this->checkout('', true, true);
        }

        $data['id'] = $this->getKey();
        $data['business_id'] = $this->business_id;
        $data['channel'] = $this->channel;
        $data['customer_id'] = $this->business_customer_id;
        $data['customer'] = [
            'name' => $this->customer_name,
            'email' => $this->customer_email,
            'phone_number' => $this->customer_phone_number,
            'address' => [
                'street' => $this->customer_street,
                'city' => $this->customer_city,
                'state' => $this->customer_state,
                'postal_code' => $this->customer_postal_code,
                'country' => $this->customer_country,
            ],
        ];

        $currency = $this->currency;

        $data['currency'] = $currency;

        $data['order_discount_name'] = $this->automatic_discount_reason;
        $data['order_discount_amount'] = getReadableAmountByCurrency($currency, $this->automatic_discount_amount);
        $data['line_item_discount_amount'] = getReadableAmountByCurrency($currency, $this->line_item_discount_amount);
        $data['line_item_tax_amount'] = getReadableAmountByCurrency($currency, $this->line_item_tax_amount);
        $data['additional_discount_amount'] = getReadableAmountByCurrency($currency, $this->additional_discount_amount);
        $data['total_discount_amount'] = getReadableAmountByCurrency($currency, $this->getTotalDiscountAmount());
        $data['amount'] = getReadableAmountByCurrency($currency, $this->amount);
        $data['status'] = $this->status;
        $data['remark'] = $this->remark;

        if ($data['channel'] === Channel::LINK_SENT && $data['status'] === OrderStatus::REQUIRES_CUSTOMER_ACTION) {
            $data[$data['channel']] = [
                'charge_url' => URL::route('home'),
                'expires_at' => optional($this->expires_at)->toAtomString(),
            ];
        }

        $data['products_count'] = $this->products_count;
        $data['products'] = [];

        if ($this->relationLoaded('products')) {
            foreach ($this->products->sortBy('created_at') as $product) {
                $thisProduct = [
                    'id' => $product->getKey(),
                    'stock_keeping_unit' => $product->stock_keeping_unit,
                    'name' => $product->name,
                    'description' => $product->description,
                    'weight' => $product->weight,
                    'length' => $product->length,
                    'width' => $product->width,
                    'depth' => $product->depth,
                    'variations' => [
                        [
                            'key' => $product->variation_key_1,
                            'value' => $product->variation_value_1,
                        ],
                        [
                            'key' => $product->variation_key_2,
                            'value' => $product->variation_value_2,
                        ],
                        [
                            'key' => $product->variation_key_3,
                            'value' => $product->variation_value_3,
                        ],
                    ],
                    'quantity' => $product->quantity,
                    'tax' => [
                        'name' => $product->tax_name,
                        'rate' => (float) bcmul((string) $product->tax_rate, '100', 2),
                    ],
                    'unit_price' => getReadableAmountByCurrency($currency, $product->unit_price),
                    'total_price' => getReadableAmountByCurrency($currency, $product->price),
                    'total_tax' => getReadableAmountByCurrency($currency, $product->tax_amount),
                    'total_discount' => getReadableAmountByCurrency($currency, $product->discount_amount),
                    'remark' => $product->remark,
                ];

                if ($product->relationLoaded('image') && $product->image) {
                    $thisProduct['image'] = (new Image($product->image))->toArray($request);
                }

                $data['products'][] = $thisProduct;
            }
        }

        if ($this->relationLoaded('charges')) {
            foreach ($this->charges->sortBy('created_at') as $charge) {
                $chargeCurrency = $charge->currency;

                $data['charges'][] = [
                    'id' => $charge->getKey(),
                    'business_id' => $charge->business_id,
                    'channel' => $charge->channel,
                    'customer_id' => $charge->business_customer_id,
                    'payment_provider' => [
                        'code' => $charge->payment_provider,
                        'account_id' => $charge->payment_provider_account_id,

                        'charge' => [
                            'type' => $charge->payment_provider_charge_type,
                            'id' => $charge->payment_provider_charge_id,
                            'method' => $charge->payment_provider_charge_method,
                            'transfer_type' => $charge->payment_provider_transfer_type,
                        ],
                    ],
                    'currency' => $charge->currency,
                    'amount' => getReadableAmountByCurrency($chargeCurrency, $charge->amount),
                    'fixed_fee' => getReadableAmountByCurrency($chargeCurrency, $charge->fixed_fee),
                    'discount_fee' => getReadableAmountByCurrency($chargeCurrency, $charge->discount_fee),
                    'discount_fee_rate' => (float) bcmul((string) $charge->discount_fee_rate, '100', 2),
                    'status' => $charge->status,
                    'failed_reason' => $charge->failed_reason,
                    'remark' => $charge->remark,
                    'created_at' => $charge->created_at->toAtomString(),
                    'updated_at' => $charge->updated_at->toAtomString(),
                    'closed_at' => optional($charge->closed_at)->toAtomString(),
                ];
            }
        }

        if ($request->get('with_request_details')) {
            $requestData = $this->request_data;

            $data['request'] = [
                'ip_address' => $this->request_ip_address,
                'method' => $this->request_method,
                'url' => $this->request_url,
                'device' => [
                    'type' => $requestData['device']['type'] ?? null,
                    'name' => $requestData['device']['name'] ?? null,
                ],
                'platform' => [
                    'name' => $requestData['platform']['name'] ?? null,
                    'version' => $requestData['platform']['version'] ?? null,
                ],
                'browser' => [
                    'name' => $requestData['browser']['name'] ?? null,
                    'version' => $requestData['browser']['version'] ?? null,
                ],
                'executor' => [
                    'id' => $this->executor_id,
                    'first_name' => $requestData['executor']['first_name'],
                    'last_name' => $requestData['executor']['last_name'],
                    'email' => $requestData['executor']['email'],
                ],
                'country' => $this->request_country,
                'location' => $requestData['location'] ?? null,
                'coordinate' => [
                    'latitude' => $requestData['coordinate']['latitude'] ?? null,
                    'longitude' => $requestData['browser']['longitude'] ?? null,
                ],
            ];
        }

        $data['created_at'] = $this->created_at->toAtomString();
        $data['updated_at'] = $this->updated_at->toAtomString();
        $data['closed_at'] = optional($this->closed_at)->toAtomString();

        return $data;
    }
}
