<?php

namespace HitPay\Business\Contracts;

use App\Business\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface HasCustomer
{
    public function setCustomer(Customer $customer, bool $overwrite = true);

    public function customer() : BelongsTo;
}
