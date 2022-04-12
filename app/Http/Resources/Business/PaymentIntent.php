<?php

namespace App\Http\Resources\Business;

use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\PaymentProvider;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Business\PaymentIntent
 */
class PaymentIntent extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function toArray($request)
    {
        $data['id'] = $this->getKey();
        $data['charge_id'] = $this->business_charge_id;

        if ($this->relationLoaded('charge')) {
            $data['charge'] = new Charge($this->charge);
        }

        $data['object_type'] = $this->payment_provider_object_type;
        $data['object_id'] = $this->payment_provider_object_id;
        $data['method'] = $this->payment_provider_method;
        $data['currency'] = $this->currency;
        $data['amount'] = $this->amount;
        $data['status'] = $this->status;

        if ($data['status'] === 'failed') {
            $data['failed_reason'] = $this->failed_reason;
        }

        switch ($this->payment_provider) {
            case PaymentProvider::STRIPE_SINGAPORE:
            case PaymentProvider::STRIPE_MALAYSIA:
                if ($data['object_type'] === 'payment_intent') {
                    if ($this->status === 'requires_source_action'
                        || $this->status === 'requires_action'
                        || $this->payment_provider_method === PaymentMethodType::CARD_PRESENT
                        || $this->payment_provider_method === PaymentMethodType::FPX
                        || $this->payment_provider_method === PaymentMethodType::GRABPAY) {
                        if (isset($this->data['client_secret'])) {
                            $clientSecret = $this->data['client_secret'];
                        } elseif (isset($this->data['stripe']['payment_intent']['client_secret'])) {
                            $clientSecret = $this->data['stripe']['payment_intent']['client_secret'];
                        }
                    }

                    $data['payment_intent']['client_secret'] = $clientSecret ?? null;
                } elseif ($data['object_type'] === 'source') {
                    // TODO - KIV
                    //   ---------->>>
                    //   - WeChat pay of Stripe is not available for Malaysia, actually.
                    //
                    if ($data['method'] === 'wechat') {
                        $data['wechat'] = [
                            'qr_code_url' => ( $this->data['stripe']['source'] ?? $this->data )['wechat']['qr_code_url'] ?? null,
                        ];
                    } elseif ($data['method'] === 'alipay') {
                        $data['alipay'] = [
                            'redirect_url' => ( $this->data['stripe']['source'] ?? $this->data )['redirect']['url'] ?? null,
                        ];
                    }
                }

                break;

            case PaymentProvider::DBS_SINGAPORE:
                if ($data['method'] === PaymentMethodType::PAYNOW && $data['status'] === 'pending') {
                    $data[PaymentMethodType::PAYNOW] = [
                        'qr_code_data' => $this->data['data'],
                    ];
                }

                break;

            case PaymentProvider::SHOPEE_PAY:
                $data['qr_url'] = $this->data['qr_url'];
                $data['qr_content'] = $this->data['qr_content'];
                break;

            case PaymentProvider::HOOLAH:
                $data['redirect_url'] =
                    'https://'.config('services.hoolah.redirect_domain').'/?ORDER_CONTEXT_TOKEN='.$this->data['orderContextToken'].'&platform=bespoke&version=1.0.1';
                break;

            case PaymentProvider::GRABPAY:
            case PaymentProvider::ZIP:
                $data['redirect_url'] = $this->data['redirect_uri'];
                break;
        }

        $data['created_at'] = $this->created_at->toAtomString();
        $data['updated_at'] = $this->updated_at->toAtomString();

        if ($expiresAt = $this->expires_at) {
            $data['expires_at'] = $expiresAt->toAtomString();
        }

        return $data;
    }
}
