<?php

namespace App\Actions\Business\Stripe\Payouts;

use App\Business;
use App\Notifications\Business\Stripe\NotifyPayoutFailed;
use Illuminate\Support\Facades;
use Stripe\Payout;

class HandleFailed extends Action
{
    public function process()
    {
        if ($this->businessTransfer instanceof Business\Transfer) {
            $this->syncBusinessTransfer();
        } else {
            $this->createBusinessTransfer();

            Facades\Log::info(
                "The payout (Stripe ID : {$this->stripePayout->id}) is not found, a new record is created, check the business transfer (ID : {$this->businessTransfer->getKey()})."
            );
        }

        if ($this->businessTransfer->status !== Payout::STATUS_FAILED) {
            Facades\Log::info(
                "The payout (Stripe ID : {$this->stripePayout->id}) is not failed, the handler will stop here, check the business transfer (ID : {$this->businessTransfer->getKey()})."
            );

            return;
        }

        // Check https://stripe.com/docs/api/payouts/failures for failure scenarios to handle.
        //
        if (in_array($this->stripePayout->failure_code, [
            Payout::FAILURE_INVALID_ACCOUNT_NUMBER,
            'incorrect_account_holder_address',
            Payout::FAILURE_INCORRECT_ACCOUNT_HOLDER_NAME,
            'incorrect_account_holder_tax_id',
            Payout::FAILURE_NO_ACCOUNT,
        ])) {
            $this->business->notify(new NotifyPayoutFailed($this->stripePayout->failure_message));

            return;
        }

        Facades\Log::critical(
            "The payout (Stripe ID : {$this->stripePayout->id}) is failed, got message: `{$this->stripePayout->failure_message}`, check the business transfer (ID : {$this->businessTransfer->getKey()})."
        );
    }
}
