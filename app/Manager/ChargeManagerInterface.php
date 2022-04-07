<?php

namespace App\Manager;

use App\Business;
use App\Business\Charge;
use App\Business\PaymentIntent;
use App\Http\Resources\Business\Charge as ChargeResource;

interface ChargeManagerInterface
{   
    public function createRequiresPaymentMethod(Business $business, array $data) : Charge;

    public function captureStripePaymentIntent(Business $business, PaymentIntent $paymentIntent);

    public function createCash(Business $business, Charge $charge) : ChargeResource;
}