<?php

namespace App\Actions\Business\Payout\DBS;

use App\Actions\Business\Payout\Action as BaseAction;

abstract class Action extends BaseAction
{
    protected int $perPage;

    /**
     * @param int $perPage
     * @return $this
     */
    public function setPerPage(int $perPage) : self
    {
        $this->perPage = $perPage;

        return $this;
    }
}
