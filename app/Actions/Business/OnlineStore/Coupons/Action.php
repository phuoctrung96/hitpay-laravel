<?php

namespace App\Actions\Business\OnlineStore\Coupons;

use App\Actions\Business\Action as BaseAction;
use App\Business\Coupon;

abstract class Action extends BaseAction
{
    protected Coupon $coupon;

    protected array $appliesToIds = [];

    protected array $cart;

    /**
     * @param Coupon $coupon
     * @return $this
     */
    public function setCoupon(Coupon $coupon): self
    {
        $this->coupon = $coupon;

        $this->appliesToIds = $coupon->applies_to_ids;

        return $this;
    }

    /**
     * @param array $cart
     * @return void
     */
    public function setCart(array $cart): self
    {
        $this->cart = $cart;

        return $this;
    }

    /**
     * @throws \Exception
     */
    protected function validateCart(): void
    {
        if ($this->cart === null) {
            throw new \Exception("Cart not set!");
        }

        if (!isset($this->cart['products'])) {
            throw new \Exception("Set coupon with cart empty product!");
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function validateCoupon()
    {
        if ($this->coupon === null) {
            throw new \Exception("Coupon not set!");
        }

        if ($this->coupon->business->getKey() !== $this->business->getKey()) {
            throw new \Exception("There is coupon ID {$this->coupon->getKey()} that no match with business ID {$this->business->getKey()}");
        }

        $this->validateCart();
    }
}
