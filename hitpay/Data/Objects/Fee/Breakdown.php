<?php

namespace HitPay\Data\Objects\Fee;

use HitPay\Data\Objects\Base;

/**
 * @property-read int $fixed_fee_amount
 * @property-read int $discount_fee_amount
 * @property-read int $total_amount
 */
class Breakdown extends Base
{
    /**
     * Breakdown Constructor
     *
     * @param  int  $fixedFeeAmount
     * @param  int  $discountFeeAmount
     */
    public function __construct(int $fixedFeeAmount, int $discountFeeAmount)
    {
        $this->data['fixed_fee_amount'] = $fixedFeeAmount;
        $this->data['discount_fee_amount'] = $discountFeeAmount;
        $this->data['total_amount'] = (int) bcadd($this->data['fixed_fee_amount'], $this->data['discount_fee_amount']);
    }
}
