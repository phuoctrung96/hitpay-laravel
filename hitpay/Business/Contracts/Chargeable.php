<?php

namespace HitPay\Business\Contracts;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
interface Chargeable
{
    public function getAmount() : int;

    public function getChannel() : string;

    public function getCurrency() : string;
}
