<?php

namespace App\Events\Business;

use App\Business\Charge;
use Illuminate\Queue\SerializesModels;

class RecurrentChargeSucceeded
{
    use SerializesModels;

    /**
     * The business.
     *
     * @var Charge
     */
    public $charge;

    /**
     * Create a new event instance.
     *
     * @param Charge %charge
     */
    public function __construct(Charge $charge)
    {
        $this->charge = $charge;
    }
}
