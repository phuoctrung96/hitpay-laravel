<?php

namespace HitPay\Stripe\CustomAccount;

use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use Illuminate\Support\Facades\Cache;

class Sync extends CustomAccount
{
    /**
     * Sync custom account data and information from Stripe.
     *
     * @param  string|null  $state
     * @param  bool  $strict
     *
     * @throws \Throwable
     */
    public function handle(?string $state, bool $strict = true) : void
    {
        if ($strict) {
            if (is_null($state)) {
                throw $this->exception("A 'state' value is required for this action.", InvalidStateException::class);
            }

            $cacheKey = $this->generateSyncStateCacheKey($state);

            if (Cache::has($cacheKey)) {
                $this->syncAccount();

                Cache::forget($cacheKey);
            }
        } else {
            $this->syncAccount();
        }
    }
}
