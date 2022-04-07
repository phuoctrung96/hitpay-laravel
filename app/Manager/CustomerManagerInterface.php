<?php

namespace App\Manager;

use App\Business;
use App\Business\Customer;

interface CustomerManagerInterface
{
    public function getFindOrCreateByEmail(Business $business, string $email, ?string $name = null, ?string $phone = null) : Customer;

    public function getFindByBusinessAndEmail(Business $business, string $email) : ?Customer;
}
