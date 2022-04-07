<?php

namespace App\Actions\Business\Settings\BankAccount;

use App\Actions\Business\Action as BaseAction;
use App\Business;
use App\Models\Business\BankAccount as Model;
use Exception;

abstract class Action extends BaseAction
{
    protected ?Model $bankAccount = null;

    protected ?string $bankAccountId = null;

    protected bool $requireBranchCodeForCertainCountries = true;

    /**
     * Set the bank account.
     *
     * @param  \App\Models\Business\BankAccount  $bankAccount
     *
     * @return $this
     * @throws \Exception
     */
    public function bankAccount(Model $bankAccount) : self
    {
        if ($this->businessId && $this->businessId !== $bankAccount->business_id) {
            throw new Exception("The bank account (ID : {$bankAccount->getKey()}) doesn't belonged to the business (ID : {$this->businessId})");
        }

        $this->bankAccount = $bankAccount;
        $this->bankAccountId = $bankAccount->getKey();

        return $this;
    }

    /**
     * Initiate a new instance starts with setting the bank account.
     *
     * @param  \App\Models\Business\BankAccount  $bankAccount
     *
     * @return static
     * @throws \Exception
     */
    public static function withBankAccount(Model $bankAccount) : self
    {
        return ( new static )->bankAccount($bankAccount);
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function business(Business $business) : self
    {
        if ($this->bankAccount && $this->bankAccount->business_id !== $business->getKey()) {
            throw new Exception("The business (ID : {$business->getKey()}) has no right to the bank account (ID : {$this->bankAccountId})");
        }

        return parent::business($business);
    }

    public function requireBranchCodeForCertainCountries() : self
    {
        $this->requireBranchCodeForCertainCountries = true;

        return $this;
    }

    public function canIgnoreBranchCodeForCertainCountries() : self
    {
        $this->requireBranchCodeForCertainCountries = false;

        return $this;
    }
}
