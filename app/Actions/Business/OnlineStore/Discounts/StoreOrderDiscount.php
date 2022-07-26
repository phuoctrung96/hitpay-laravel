<?php

namespace App\Actions\Business\OnlineStore\Discounts;

use App\Business\Discount;
use App\Business\Order;
use App\Business\OrderedDiscount;

class StoreOrderDiscount extends Action
{
    private Order $order;
    private array $discountData;

    /**
     * @param Order $order
     * @return $this
     */
    public function setOrder(Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @param array $discountData
     * @return $this
     */
    public function setDiscountData(array $discountData): self
    {
        $this->discountData = $discountData;

        return $this;
    }

    /**
     * @param array $discountData
     * @return bool
     * @throws \Exception
     */
    public function process(): bool
    {
        if ($this->order->business->getKey() !== $this->business->getKey()) {
            throw new \Exception("There is order ID {$this->order->getKey()} that not match with business ID {$this->business->getKey()}");
        }

        if ($this->discountData === null) {
            throw new \Exception("There is order ID {$this->order->getKey()} that not have discount data");
        }

        foreach ($this->discountData as $discountData) {
            $discount = Discount::find($discountData['id']);

            if (!$discount instanceof Discount) {
                continue;
            }

            $businessOrderedDiscount = new OrderedDiscount();
            $businessOrderedDiscount->order_id = $this->order->getKey();
            $businessOrderedDiscount->business_id = $this->business->getKey();
            $businessOrderedDiscount->discount_id = $discount->getKey();
            $businessOrderedDiscount->discount_data = $discountData['data'];
            $businessOrderedDiscount->save();
        }

        return true;
    }
}
