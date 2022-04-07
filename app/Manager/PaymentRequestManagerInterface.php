<?php

namespace App\Manager;

use App\Business;
use App\Business\PaymentRequest;

interface PaymentRequestManagerInterface
{
    public function create(array $data, string $businessKey, array $paymentMethods, Business $platform = null) : PaymentRequest;
}