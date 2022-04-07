<?php

namespace App\Actions\Business\Stripe\Payouts;

use App\Business;
use Illuminate\Support\Facades;

class Update extends Action
{
    /**
     * Sync the latest data of the Stripe payout to the business transfer, create if not exists.
     *
     * @return void
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function process() : void
    {
        if ($this->businessTransfer instanceof Business\Transfer) {
            $this->syncBusinessTransfer();
        } else {
            $this->createBusinessTransfer();

            Facades\Log::info("The payout (Stripe ID : {$this->stripePayout->id}) is not found, a new record is created, check the business transfer (ID : {$this->businessTransfer->getKey()}).");
        }
    }
}
