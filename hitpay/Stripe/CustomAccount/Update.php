<?php

namespace HitPay\Stripe\CustomAccount;

use Illuminate\Support\Facades;

class Update extends CustomAccount
{
    /**
     * Basically we are syncing the information of our user's business account to Stripe, then get the latest account
     * data from Stripe and store it to the related payment provider.
     *
     * @param  bool  $useStripeUpdate
     *
     * @return \App\Business\PaymentProvider|\Illuminate\Http\RedirectResponse
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function handle(bool $useStripeUpdate = false)
    {
        if ($useStripeUpdate) {
            return Facades\Response::redirectTo($this->generateCustomAccountLink('account_update'));
        }

        return $this->updateAccount($this->generateData());
    }
}
