<?php

namespace App\Actions\Business\Payout\Stripe;

use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Enumerations\PaymentProviderAccountType;

class RetrieveCustomConnect extends Action
{
    /**
     * @return array
     * @throws \Exception
     */
    public function process() : array
    {
        $provider = $this->business->paymentProviders()
            ->where('payment_provider', $this->business->payment_provider)
            ->first();

        if ($provider == null) {
            throw new \Exception("Empty payment provider when
                retrieve stripe payout from transfer");
        }

        if (!in_array($this->business->payment_provider, [PaymentProviderEnum::STRIPE_MALAYSIA, PaymentProviderEnum::STRIPE_SINGAPORE])) {
            throw new \Exception("Invalid payment provider {$this->business->payment_provider} when
                retrieve stripe payout from transfer");
        }

        if (!$provider->payment_provider_account_type == PaymentProviderAccountType::STRIPE_CUSTOM_TYPE) {
            throw new \Exception("Invalid payment provider account type {$provider->payment_provider_account_type}
            when retrieve stripe payout from transfer with business id {$this->business->getKey()}");
        }

        $transfers = $this->business->transfers()
            ->with('charges')
            ->where('payment_provider', $this->business->payment_provider)
            ->where('payment_provider_transfer_type', 'stripe')
            ->where('payment_provider_transfer_method', 'payout')
            ->orderByDesc('id')->paginate($this->perPage);

        return [
            'provider' => $provider,
            'transfers' => $transfers,
        ];
    }
}
