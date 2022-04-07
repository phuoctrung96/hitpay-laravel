<?php

namespace App\Manager;

interface FactoryPaymentIntentManagerInterface
{
    public function create(string $method) : PaymentIntentManagerInterface;
}