<?php

namespace App\Actions\Business\Stripe\Payouts;

use App\Business;
use Illuminate\Support\Facades;

class Store extends Action
{
    /**
     * Store the Stripe payout of a business, and request a report.
     *
     * @return void
     * @throws \App\Actions\Exceptions\BadRequest
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function process()
    {
        if ($this->businessTransfer instanceof Business\Transfer) {
            $this->syncBusinessTransfer();

            Facades\Log::info("The payout (Stripe ID : {$this->stripePayout->id}) is already synced earlier, check the business transfer (ID : {$this->businessTransfer->getKey()}).");
        } else {
            $this->createBusinessTransfer();
        }
    }
}
