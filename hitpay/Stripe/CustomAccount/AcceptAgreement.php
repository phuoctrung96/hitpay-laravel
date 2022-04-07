<?php

namespace HitPay\Stripe\CustomAccount;

use App\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades;

class AcceptAgreement extends CustomAccount
{
    /**
     * Submit the agreement acceptance to Stripe for the business accounts of our users, and get the latest account
     * data from Stripe and store it to the related payment provider.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \App\Business\PaymentProvider
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function handle(Request $request) : Business\PaymentProvider
    {
        return $this->updateAccount([
            'tos_acceptance' => [
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'date' => Facades\Date::now()->getTimestamp(),
            ],
        ]);
    }
}
