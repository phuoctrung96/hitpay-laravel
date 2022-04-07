<?php

namespace App\Actions\Business\Settings\BankAccount;

use HitPay\Stripe\CustomAccount\ExternalAccount;

class SetAsDefaultForStripePayout extends Action
{
    /**
     * Set bank account as default for Stripe payout.
     *
     * @return bool
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function process() : bool
    {
        // TODO - KEEP IN VIEW
        //   ------------------->>>
        //   If we want to update the country, currency, swift code or number of the bank account, for Stripe, we
        //   will have to delete the existing account in Stripe and create a new one. Stripe doesn't allow these
        //   values to be changed.
        //
        if ($this->bankAccount->stripe_external_account_id === null) {
            $externalAccountHandler = ExternalAccount\Create::new($this->business->payment_provider);
        } else {
            $externalAccountHandler = ExternalAccount\Update::new($this->business->payment_provider);
        }

        if ($externalAccountHandler instanceof ExternalAccount\ExternalAccount) {
            $externalAccountHandler->setBusiness($this->business)->handle($this->bankAccount, true);
        }

        return true;
    }
}
