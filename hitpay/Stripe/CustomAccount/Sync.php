<?php

namespace HitPay\Stripe\CustomAccount;

use App\Business\PaymentProvider;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use Illuminate\Support\Facades\Cache;
use Session;

class Sync extends CustomAccount
{
    /**
     * Sync custom account data and information from Stripe.
     *
     * @param  string|null  $state
     * @param  bool  $strict
     *
     * @return \App\Business\PaymentProvider
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function handle(?string $state, bool $strict = true) : PaymentProvider
    {
        if ($strict) {
            if (is_null($state)) {
                throw $this->exception("A 'state' value is required for this action.", InvalidStateException::class);
            }

            $stateExists = Session::pull($this->generateSyncStateKey($state), false);

            if (!$stateExists) {
                throw $this->exception("Invalid 'state' value is given for this action.", InvalidStateException::class);
            }
        }

        return $this->syncAccount();
    }
}
