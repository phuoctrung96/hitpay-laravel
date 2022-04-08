<?php

namespace App\Actions\Business\Onboard\Paynow;

use App\Actions\Business\Action as BaseAction;

abstract class Action extends BaseAction
{
    protected bool $isEnableBankAccount = false;

    /**
     * @return $this
     */
    public function enableBankAccount()
    {
        $this->isEnableBankAccount = true;

        return $this;
    }
}
