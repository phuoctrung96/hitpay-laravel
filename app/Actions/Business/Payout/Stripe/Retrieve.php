<?php

namespace App\Actions\Business\Payout\Stripe;

use HitPay\Stripe\Payout;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class Retrieve extends Action
{
    /**
     * @throws \ReflectionException
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function process() : array
    {
        $provider = $this->business->paymentProviders()
            ->where('payment_provider', $this->business->payment_provider)
            ->first();

        $payouts = Payout::new($provider->payment_provider, $provider->payment_provider_account_id)->index(25);

        $data = Collection::make();

        foreach ($payouts as $payout) {
            /**
             * @var \Stripe\Payout $payout
             */
            $data->add([
                'id' => $payout->id,
                'amount' => getFormattedAmount($payout->currency, $payout->amount),
                'arrival_date' => $payout->arrival_date
                    ? Date::createFromTimestamp($payout->arrival_date)->toDateString()
                    : null,
                'created_date' => $payout->created
                    ? Date::createFromTimestamp($payout->created)->toDateString()
                    : null,
                'description' => $payout->description,
                'source_type' => $payout->source_type,
                'status' => $payout->status,
                'type' => $payout->type,
            ]);
        }

        return [
            'data' => $data,
            'provider' => $provider,
        ];
    }
}
