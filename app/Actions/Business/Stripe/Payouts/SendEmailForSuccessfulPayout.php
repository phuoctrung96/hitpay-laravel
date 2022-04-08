<?php

namespace App\Actions\Business\Stripe\Payouts;

use App\Business;
use App\Enumerations\Business\Event;
use App\Models\Business\BankAccount;
use App\Notifications\Business\Stripe\NotifySuccessfulPayout;
use Illuminate\Support\Facades;
use Stripe\Payout;

class SendEmailForSuccessfulPayout extends Action
{
    /**
     * Set the business transfer.
     *
     * @param  \App\Business\Transfer  $businessTransfer
     *
     * @return $this
     * @throws \Exception
     */
    public function businessTransfer(Business\Transfer $businessTransfer) : self
    {
        if ($this->business->getKey() !== $businessTransfer->business_id) {
            throw new \Exception(
                "The transfer (ID : {$businessTransfer->getKey()}) doesn't belonged to
                the business (ID : {$this->business->getKey()})"
            );
        }

        if ($businessTransfer->payment_provider_transfer_type !== 'stripe') {
            throw new \Exception(
                "The payment provider transfer type of the transfer (ID : {$businessTransfer->getKey()})
                must be 'stripe' to continue."
            );
        } else {
            $payoutObjectName = Payout::OBJECT_NAME;

            if ($businessTransfer->payment_provider_transfer_method !== $payoutObjectName) {
                throw new \Exception(
                    "The payment provider transfer method of the transfer (ID : {$businessTransfer->getKey()})
                    must be '{$payoutObjectName}' to continue."
                );
            }
        }

        $this->businessTransfer = $businessTransfer;

        return $this;
    }

    /**
     * Set the business.
     *
     * @param  \App\Business  $business
     *
     * @return $this
     * @throws \Exception
     */
    public function business(Business $business) : self
    {
        $this->business = $business;

        return $this;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function process() : void
    {
        $csvFile = $this->getCsvFile();

        $bankAccount = $this->getBusinessBankAccount();

        $notification = new NotifySuccessfulPayout($this->businessTransfer, $bankAccount, $csvFile);

        if ($this->business->subscribedEvents()->where('event', Event::DAILY_PAYOUT)->first() != null)
            $this->business->notify($notification);
    }

    /**
     * @param Business $business
     * @return static
     * @throws \Exception
     */
    public static function withBusiness(Business $business) : self
    {
        return ( new static )->business($business);
    }

    /**
     * @throws \Exception
     */
    private function getCsvFile(): string
    {
        $transferData = $this->businessTransfer->data;

        $file = $transferData['file'] ?? null;

        if ($file == null) {
            throw new \Exception("File null from business transfer id {$this->businessTransfer->getKey()}
                    when trying sending payout email.");
        }

        if (!is_array($file)) {
            throw new \Exception("File format not array from business transfer id {$this->businessTransfer->getKey()}
                    when trying sending payout email.");
        }

        $filePath = $file['path'] ?? null;

        if (!$filePath) {
            throw new \Exception("File path empty from business transfer id {$this->businessTransfer->getKey()}
                    when trying sending payout email.");
        }

        try {
            return Facades\Storage::get($filePath);
        } catch (\Exception $exception) {
            throw new \Exception("File path have issue from business transfer id {$this->businessTransfer->getKey()}
                when trying sending payout email with message {$exception->getMessage()}");
        }
    }

    /**
     * @return BankAccount
     * @throws \Exception
     */
    private function getBusinessBankAccount() : BankAccount
    {
        $transferData = $this->businessTransfer->data;

        $stripe = $transferData['stripe'] ?? null;

        if ($stripe == null) {
            throw new \Exception("stripe key null from business transfer id {$this->businessTransfer->getKey()}
                when trying sending payout email.");
        }

        $stripePayout = $stripe['payout'] ?? null;

        if ($stripePayout == null) {
            throw new \Exception("payout key null from business transfer id {$this->businessTransfer->getKey()}
                when trying sending payout email.");
        }

        $destinationPayout = $stripePayout['data']['object']['destination'] ?? null;

        if ($destinationPayout == null) {
            throw new \Exception("payout destination null from business transfer id {$this->businessTransfer->getKey()}
                when trying sending payout email.");
        }

        // This is dangerous, the Stripe external account ID will change when an update is made, and will cause error
        // here.
        //
        $bankAccount = $this->business->bankAccounts()
            ->where('stripe_external_account_id', $destinationPayout)
            ->first();

        if ($bankAccount == null) {
            throw new \Exception("bank account null from business transfer id {$this->businessTransfer->getKey()}
                when trying sending payout email.");
        }

        return $bankAccount;
    }
}
