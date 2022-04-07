<?php

namespace App\Actions\Business\Payout\DBS;

use App\Enumerations\PaymentProvider as PaymentProviderEnum;

class Retrieve extends Action
{
    /**
     * @return array
     */
    public function process() : array
    {
        $provider = $this->business->paymentProviders()
            ->where('payment_provider', $this->business->payment_provider)
            ->first();

        $transfers = $this->business->transfers()
            ->with('charges')
            ->where('payment_provider', PaymentProviderEnum::DBS_SINGAPORE)
            ->orderByDesc('id')->paginate($this->perPage);

        return [
            'provider' => $provider,
            'transfers' => $transfers,
        ];
    }
}
