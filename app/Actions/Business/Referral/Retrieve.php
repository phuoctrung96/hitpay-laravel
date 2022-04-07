<?php

namespace App\Actions\Business\Referral;

use App\Enumerations\Business\Wallet\Type;

class Retrieve extends Action
{
    /**
     * @return array
     */
    public function process() : array
    {
        $wallet = $this->business->wallet(Type::AVAILABLE, $this->business->currency);

        $amount = $wallet->transactions()->where('event', 'business_referral_commission')->sum('amount');
        $amount = getFormattedAmount($wallet->currency, $amount, false);

        return compact('amount');
    }
}
