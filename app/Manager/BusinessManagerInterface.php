<?php

namespace App\Manager;

use App\Business;
use Illuminate\Support\Collection;

interface BusinessManagerInterface
{
    public function createStripeConnectionToken(Business $business);

    // public function getAvailableBanks(Business $business);

    public function getBusinessesConnectedToXero(): Collection;
}
