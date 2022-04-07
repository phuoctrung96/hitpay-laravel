<?php


namespace App\Services;


use App\Business\BusinessReferralPayout;
use App\Business\Charge;
use Illuminate\Support\Str;

class BusinessReferralPayoutService
{
    public function handle(Charge $charge): ?BusinessReferralPayout
    {
        if($charge->payment_provider_charge_method === 'cash') {
            return null;
        }

        if(!$referredBy = $charge->business->referredBy) {
            return null;
        }

        $fee = bcmul($charge->amount,$charge->business->referredBy->referral_fee);
        if($fee < 1) {
            return null;
        }

        return BusinessReferralPayout::create([
            'id' => Str::uuid(),
            'business_id' => $referredBy->business->id,
            'referred_business_id' => $charge->business->id,
            'transaction_amount' => $charge->amount,
            'charge_id' => $charge->id,
            'referral_fee' => $fee,
            'paid_status' => false,
            'currency' => $charge->currency,
        ]);
    }
}
