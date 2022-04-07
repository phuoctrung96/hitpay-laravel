<?php

namespace App\Manager;

use App\Business;
use App\Business\Charge;
use App\Http\Resources\Business\PaymentIntent as PaymentIntentResource;

interface PaymentIntentManagerInterface
{
    public function create(Charge $charge, Business $business) : PaymentIntentResource;
}