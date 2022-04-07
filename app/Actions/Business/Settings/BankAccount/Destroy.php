<?php

namespace App\Actions\Business\Settings\BankAccount;

use App\Actions\Exceptions\BadRequest;
use HitPay\Stripe\CustomAccount\ExternalAccount;
use Illuminate\Support\Facades;

class Destroy extends Action
{
    /**
     * Delete bank account.
     *
     * @return bool
     * @throws \App\Actions\Exceptions\BadRequest
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Stripe\Exception\InvalidRequestException
     * @throws \Throwable
     */
    public function process() : bool
    {
        if ($this->bankAccount->hitpay_default) {
            throw new BadRequest("The bank account can't be deleted because it is set as default for HitPay payout. Related Bank Account ID : {$this->bankAccountId}");
        }

        if ($this->bankAccount->stripe_external_account_id !== null) {
            if ($this->bankAccount->stripe_external_account_default) {
                throw new BadRequest("The bank account can't be deleted because it is set as default for Stripe custom account payout. Related Bank Account ID : {$this->bankAccountId}");
            }

            ExternalAccount\Delete::new($this->business->payment_provider)
                ->setBusiness($this->business)
                ->handle($this->bankAccount);
        }

        Facades\DB::transaction(function () {
            $this->bankAccount->delete();
        }, 3);

        return true;
    }
}
