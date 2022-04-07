<?php

namespace App\Enumerations;

class PaymentProviderAccountType extends Enumeration
{
    const STRIPE_STANDARD_TYPE = 'standard';
    const STRIPE_CUSTOM_TYPE = 'custom';
}
