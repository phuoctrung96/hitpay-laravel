<?php

namespace App\Actions\Business\Stripe\VerificationOnboard;

use HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException;
use HitPay\Stripe\CustomAccount\Exceptions\GeneralException;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use HitPay\Stripe\CustomAccount;
use Illuminate\Http\Request;
use App\Business;
use Illuminate\Support\Facades;

class AcceptAgreement extends Action
{
    /**
     * @param Request $request
     * @return Business\PaymentProvider
     * @throws AccountNotFoundException
     * @throws GeneralException
     * @throws InvalidStateException
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function process(Request $request) : Business\PaymentProvider
    {
        $handler = CustomAccount\AcceptAgreement::new($this->business->payment_provider)
            ->setBusiness($this->business);

        try {
            $handler->getCustomAccount();
        } catch (GeneralException $exception) {
            if ($exception instanceof InvalidStateException) {
                Facades\Log::critical("Trying but unable to create a bank account for the business (ID : {$this->businessId}) which is not using Stripe custom account.");
            } elseif ($exception instanceof AccountNotFoundException) {
                Facades\Log::critical("Trying but unable to create a bank account for a non-Stripe custom connected business account (ID : {$this->businessId}).");
            }

            throw $exception;
        }

        return $handler->handle($request);
    }
}
