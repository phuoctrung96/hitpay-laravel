<?php

namespace App\Http\Resources\Business;

use App\Business\Order as OrderModel;
use App\Enumerations\Business\Channel;
use App\Enumerations\Business\ChargeStatus;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

/**
 * @mixin \App\Business\Charge
 */
class Charge extends JsonResource
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

        if ($this->relationLoaded('target') && $this->target instanceof OrderModel) {
            $data['order'] = (new Order($this->target))->toArray($request);
        }

        if (in_array($this->payment_provider_charge_method, [
            'card',
            'card_present',
        ])) {
            if (isset($this->data['payment_method_details']['card'])) {
                $card = $this->data['payment_method_details']['card'];
            } elseif (isset($this->data['source']['card'])) {
                $card = $this->data['source']['card'];
            } elseif (isset($this->data['source'])) {
                $card = $this->data['source'];
            } elseif (isset($this->data['payment_method_details']['card_present'])) {
                $card = $this->data['payment_method_details']['card_present'];
            }

            if (isset($card['country'])) {
                $countryCode = $card['country'];

                if (Lang::has('misc.country.'.$countryCode)) {
                    $country = Lang::get('misc.country.'.$countryCode);
                }
            }

            $details = [
                'brand' => isset($card['brand']) ? ucwords($card['brand']) : 'Unknown',
                'last4' => $card['last4'] ?? '****',
                'country' => $country ?? null,
                'country_code' => $countryCode ?? null,
            ];
        }

        $data['payment_provider'] = [
            'code' => $this->payment_provider,
            'account_id' => $this->payment_provider_account_id,

            'charge' => [
                'type' => $this->payment_provider_charge_type,
                'id' => $this->payment_provider_charge_id,
                'method' => $this->payment_provider_charge_method,
                'transfer_type' => $this->payment_provider_transfer_type,
                'details' => $details ?? [],
            ],
        ];

        $data['currency'] = $this->currency;
        $data['amount'] = getReadableAmountByCurrency($data['currency'], $this->amount);
        $data['fixed_fee'] = getReadableAmountByCurrency($data['currency'], $this->fixed_fee);
        $data['discount_fee'] = getReadableAmountByCurrency($data['currency'], $this->discount_fee);
        $data['discount_fee_rate'] = (float) bcmul((string) $this->discount_fee_rate, '100', 2);
        $data['status'] = $this->status;
        $data['failed_reason'] = $this->failed_reason;
        $data['remark'] = $this->remark;

        if ($data['channel'] === Channel::LINK_SENT && $data['status'] === ChargeStatus::REQUIRES_CUSTOMER_ACTION) {
            $data[$data['channel']] = [
                'charge_url' => URL::route('home'), // todo create and update charge link
                'expires_at' => optional($this->expires_at)->toAtomString(),
            ];
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

        if ($this->relationLoaded('paymentIntents')) {
            $data['payment_intents'] = PaymentIntent::collection($this->paymentIntents);
        }

        $data['created_at'] = $this->created_at->toAtomString();
        $data['updated_at'] = $this->updated_at->toAtomString();
        $data['closed_at'] = optional($this->closed_at)->toAtomString();

        return $data;
    }
}
