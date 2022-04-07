<?php

namespace App\Actions\Business\Settings\BankAccount;

class SetAsDefaultForHitPayPayout extends Action
{
    public function process() : bool
    {
        $this->bankAccount->hitpay_default = true;

        $this->bankAccount->save();

        $this->bankAccount->business->bankAccounts()
            ->where('id', '!=', $this->bankAccount->getKey())
            ->where([
                'currency' => $this->bankAccount->currency,
                'country' => $this->bankAccount->country,
                'hitpay_default' => true,
            ])
            ->update([
                'hitpay_default' => false,
            ]);

        return true;
    }
}
