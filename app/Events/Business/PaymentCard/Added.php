<?php

namespace App\Events\Business\PaymentCard;

use App\Business;
use App\Business\PaymentCard;
use Illuminate\Queue\SerializesModels;

class Added
{
    use SerializesModels;

    /**
     * The related business.
     *
     * @var \App\Business
     */
    public $business;

    /**
     * The payment card added.
     *
     * @var \App\Business\PaymentCard
     */
    public $paymentCard;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Business $business, PaymentCard $paymentCard)
    {
        $this->business = $business;
        $this->paymentCard = $paymentCard;
    }
}
