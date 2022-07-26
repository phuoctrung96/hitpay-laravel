<?php

namespace App\Http\Resources\Business;

use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use HitPay\Stripe\Core;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Http\Resources\Business\PaymentProvider
 */
class PaymentProvider extends JsonResource
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
        $data['id'] = $this->id;
        $data['payment_provider'] = $this->payment_provider;
        $data['payment_provider_status'] = $this->getProviderStatus();
        $data['payment_provider_methods'] = $this->getPaymentMethodCodes();
        $data['payment_provider_integrations'] = $this->getProviderIntegrations();

        /* @var \App\Business\PaymentProvider $this */
        $data['fee'] = $this->getRateFor(
            $this->business->currency
        );

        if (in_array($data['payment_provider'], collect(Core::getCountries())->pluck('payment_provider')->toArray())) {
            $accountData = $this->data;

            $data['payment_provider_account'] = [
                'id' => $this->payment_provider_account_id,
                'type' => $accountData['type'] ?? null,
                'name' => $accountData['business_name'] ?? null,
                'display_name' => $accountData['display_name'] ?? null,
                'business_logo' => $accountData['business_logo_large'] ?? ($accountData['business_logo'] ?? null),
                'support' => [
                    'email' => $accountData['support_email'] ?? null,
                    'phone_number' => $accountData['support_phone'] ?? null,
                    'address' => [
                        'street' => implode(', ', array_filter([
                            $accountData['support_address']['line1'] ?? null,
                            $accountData['support_address']['line2'] ?? null,
                        ])),
                        'city' => $accountData['support_address']['city'] ?? null,
                        'state' => $accountData['support_address']['state'] ?? null,
                        'postal_code' => $accountData['support_address']['postal_code'] ?? null,
                        'country' => $accountData['support_address']['country'] ?? null,
                    ],
                ],
                'country' => $accountData['country'] ?? null,
                'currency' => $accountData['default_currency'] ?? null,
                'charges_enabled' => $accountData['charges_enabled'] ?? null,
                'payouts_enabled' => $accountData['payouts_enabled'] ?? null,
            ];
        }

        if ($data['payment_provider'] === 'dbs_sg') {
            $accountData = $this->data;

            $data['data'] = [
                'account' => $accountData['account'],
                'company' => $accountData['company']
            ];
        }

        $data['scopes'] = $this->token_scopes;

        return $data;
    }
}
