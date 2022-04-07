<?php

namespace HitPay\Stripe\CustomAccount\AccountLink;

use HitPay\Stripe\CustomAccount\CustomAccount;

class Generate extends CustomAccount
{
    /**
     * Generate Stripe Account Link.
     *
     * @param  string  $type
     *
     * @return string
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function handle(string $type) : string
    {
        return $this->generateCustomAccountLink($type);
    }
}
