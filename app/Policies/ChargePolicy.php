<?php

namespace App\Policies;

use App\User;
use App\Business;
use App\Business\Charge;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\Channel;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChargePolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param Charge $charge
     * @param Business $business
     *
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function viewCheckout(?User $user, Charge $charge, Business $business)
    {
        if ($business->getKey() === $charge->business_id && 
            ($charge->status === ChargeStatus::REQUIRES_PAYMENT_METHOD || $charge->status === ChargeStatus::SUCCEEDED) &&
            $charge->channel === Channel::PAYMENT_GATEWAY
        ) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * @param User $user
     * @param Charge $charge
     * @param Business $business
     *
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function operate(?User $user, Charge $charge, Business $business)
    {
        return $this->viewCheckout($user, $charge, $business);
    }
}