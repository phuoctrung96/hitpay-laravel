<?php

namespace App\Manager;

use App\Business;
use Illuminate\Support\Collection;

interface BusinessManagerInterface
{
    public function getBusinessStripeTerminalLocations(
        Business $business, Business\PaymentProvider $businessPaymentProvider
    ) : Business\StripeTerminalLocation;

    public function createStripeConnectionToken(Business $business);

    public function getBusinessesConnectedToXero(): Collection;
}
