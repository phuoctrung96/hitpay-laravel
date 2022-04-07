<?php

namespace App\Events\Business;

use App\Business;
use Illuminate\Queue\SerializesModels;

class Created
{
    use SerializesModels;

    /**
     * The business.
     *
     * @var \App\Business
     */
    public $business;

    /**
     * Create a new event instance.
     *
     * @param \App\Business $business
     */
    public function __construct(Business $business)
    {
        $this->business = $business;
    }
}
