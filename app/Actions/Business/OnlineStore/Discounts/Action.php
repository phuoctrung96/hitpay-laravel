<?php

namespace App\Actions\Business\OnlineStore\Discounts;

use App\Actions\Business\Action as BaseAction;
use App\Business\Discount;
use Illuminate\Database\Eloquent\Collection;

abstract class Action extends BaseAction
{
    protected array $cart;

    protected Discount $discount;

    protected array $appliesToIds;

    /**
     * @var Collection of Discount
     */
    protected Collection $discounts;

    /**
     * @param array $cart
     * @return $this
     */
    public function setCart(array $cart): self
    {
        $this->cart = $cart;

        return $this;
    }

    /**
     * @param Discount $discount
     * @return $this
     */
    public function setDiscount(Discount $discount): self
    {
        $this->discount = $discount;

        $this->appliesToIds = $discount->applies_to_ids;

        return $this;
    }

    /**
     * @param Collection $discounts
     * @return $this
     */
    public function setDiscounts(Collection $discounts): self
    {
        $this->discounts = $discounts;

        return $this;
    }
}
