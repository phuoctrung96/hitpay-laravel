<?php

namespace HitPay\Business;

use App\Business\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasCustomer
{
    /**
     * Get the customer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business\Customer
     */
    public function customer() : BelongsTo
    {
        return $this->belongsTo(Customer::class, 'busienss_customer_id', 'id', 'customer');
    }

    /**
     * Set customer.
     *
     * @param \App\Business\Customer|null $customer
     *
     * @return $this
     */
    public function setCustomer(Customer $customer = null, bool $overwrite = false)
    {
        $this->business_customer_id = $customer ? $customer->getKey() : null;

        if ($overwrite) {
            $this->customer_name = $customer->name ?? $this->customer_name ?? null;
            $this->customer_email = $customer->email ?? $this->customer_email ?? null;
            $this->customer_phone_number = $customer->phone_number ?? $this->customer_phone_number ?? null;
            $this->customer_street = $customer->street ?? null;
            $this->customer_city = $customer->city ?? null;
            $this->customer_state = $customer->state ?? null;
            $this->customer_postal_code = $customer->postal_code ?? null;
            $this->customer_country = $customer->country ?? null;
        }

        return $this;
    }
}
