<?php

namespace App\Actions\Business\Coupons;

use App\Actions\Business\Action as BaseAction;
use App\Business\Coupon;

abstract class Action extends BaseAction
{
    protected Coupon $coupon;

    /**
     * @param Coupon $coupon
     * @return $this
     */
    public function setCoupon(Coupon $coupon): self
    {
        $this->coupon = $coupon;

        return $this;
    }
}
