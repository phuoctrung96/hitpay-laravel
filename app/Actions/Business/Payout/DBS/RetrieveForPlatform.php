<?php

namespace App\Actions\Business\Payout\DBS;

use App\Enumerations\PaymentProvider as PaymentProviderEnum;

class RetrieveForPlatform extends Action
{
    /**
     * @return array
     */
    public function process(): array
    {
        $commissions = $this->business->commissions()
            ->with('charges')
            ->where('payment_provider', PaymentProviderEnum::DBS_SINGAPORE)
            ->orderByDesc('id')
            ->paginate();

        $provider = $this->business->paymentProviders()
            ->where('payment_provider', $this->business->payment_provider)
            ->first();

        return [
            'provider' => $provider,
            'commission' => $commissions,
        ];
    }
}
