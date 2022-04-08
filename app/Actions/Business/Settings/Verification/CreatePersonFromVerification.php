<?php

namespace App\Actions\Business\Settings\Verification;

use App\Business\Verification;
use App\Enumerations\Business\Type;
use HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException;
use HitPay\Stripe\CustomAccount\Exceptions\GeneralException;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use Illuminate\Support\Facades;

class CreatePersonFromVerification extends Action
{
    /**
     * @return Verification
     * @throws AccountNotFoundException
     * @throws GeneralException
     * @throws InvalidStateException
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function process() : Verification
    {
        $verification = $this->verification;

        $data = $this->data;

        if ($this->business->business_type == Type::COMPANY || $this->business->business_type == Type::PARTNER) {
            try {
                // call to update data stripe
                $this->updateAccount();

                $this->createPersonFromVerification($verification, $data);
            } catch (\Exception $exception) {
                Facades\Log::critical("Trying create person for the business (ID : {$this->businessId}) with error " . $exception->getMessage());
            }
        } else {
            // personal
            $businessPaymentProvider = $this->updateAccount();

            $this->createBusinessPerson($businessPaymentProvider, $verification);
        }

        // set job queue finish
        $this->updateStripeInit(1);

        return $verification->refresh();
    }
}
