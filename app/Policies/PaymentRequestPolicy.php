<?php

namespace App\Policies;

use App\Business;
use App\Business\PaymentRequest;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentRequestPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param PaymentRequest $paymentRequest
     * @param Business $business
     *
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function store(
        ?User $user, 
        ?PaymentRequest $paymentRequest, 
        Business $business
    ) {
        return $this->allow();
    }

    /**
     * @param User $user
     * @param PaymentRequest $paymentRequest
     *
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function show(?User $user, PaymentRequest $paymentRequest)
    {
        if ($paymentRequest->business->owner->getKey() === $user->getKey()) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * @param User $user
     * @param PaymentRequest $paymentRequest
     *
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function update(?User $user, PaymentRequest $paymentRequest)
    {
        return $this->show($user, $paymentRequest);
    }

    /**
     * @param User $user
     * @param PaymentRequest $paymentRequest
     *
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function destroy(?User $user, PaymentRequest $paymentRequest)
    {
        return $this->show($user, $paymentRequest);
    }
}