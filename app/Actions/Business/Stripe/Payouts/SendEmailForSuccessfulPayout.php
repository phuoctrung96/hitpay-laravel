<?php

namespace App\Actions\Business\Stripe\Payouts;

use App\Business;
use App\Enumerations\Business\Event;
use App\Notifications\Business\Stripe\NotifySuccessfulPayout;
use App\Providers\AppServiceProvider;
use Exception;
use HitPay\Data\PaymentProviders;
use Illuminate\Support\Facades;
use Stripe\Account;
use Stripe\Payout;
use Stripe\Stripe;

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
            throw new Exception(
                "The transfer (ID : {$businessTransfer->getKey()}) doesn't belonged to the business (ID : {$this->business->getKey()})"
            );
        }

        if ($businessTransfer->payment_provider_transfer_type !== 'stripe') {
            throw new Exception(
                "The payment provider transfer type of the transfer (ID : {$businessTransfer->getKey()}) must be 'stripe' to continue."
            );
        } else {
            $payoutObjectName = Payout::OBJECT_NAME;

            if ($businessTransfer->payment_provider_transfer_method !== $payoutObjectName) {
                throw new Exception(
                    "The payment provider transfer method of the transfer (ID : {$businessTransfer->getKey()}) must be '{$payoutObjectName}' to continue."
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
        if (!$this->business->subscribedEvents()->where('event', Event::DAILY_PAYOUT)->first() != null) {
            return;
        }

        $csvFile = $this->getCsvFile();

        $bankAccountId = $this->businessTransfer->data['stripe']['payout']['destination'] ?? null;

        if (!is_null($bankAccountId)) {
            $paymentProvider = PaymentProviders::all()
                ->where('code', $this->businessTransfer->payment_provider)
                ->first();

            if (!is_null($paymentProvider)) {
                $stripeSecret = Facades\Config::get("services.stripe.{$paymentProvider->getCountry()}.secret");

                if (!is_null($stripeSecret)) {
                    Stripe::setApiKey($stripeSecret);

                    $stripeAccount = Account::retrieve($this->businessTransfer->payment_provider_account_id, [
                        'stripe_version' => AppServiceProvider::STRIPE_VERSION,
                    ]);

                    $bankAccount = $stripeAccount->external_accounts->retrieve($bankAccountId);
                }
            }
        }

        $notification = new NotifySuccessfulPayout($this->businessTransfer, $bankAccount ?? null, $csvFile);

        $this->business->notifyNow($notification);
        $this->business->businessUsers()->each(function($businessUser) use ($notification) {
            if ($businessUser->isAdmin()) {
                $businessUser->user->notifyNow($notification);
            }
        });
    }

    /**
     * @throws \Exception
     */
    private function getCsvFile() : string
    {
        $transferData = $this->businessTransfer->data;

        $file = $transferData['file'] ?? null;

        if ($file == null) {
            throw new Exception("File null from business transfer id {$this->businessTransfer->getKey()} when trying sending payout email.");
        }

        if (!is_array($file)) {
            throw new Exception("File format not array from business transfer id {$this->businessTransfer->getKey()} when trying sending payout email.");
        }

        $filePath = $file['path'] ?? null;

        if (!$filePath) {
            throw new Exception("File path empty from business transfer id {$this->businessTransfer->getKey()} when trying sending payout email.");
        }

        try {
            return Facades\Storage::get($filePath);
        } catch (Exception $exception) {
            throw new Exception("File path have issue from business transfer id {$this->businessTransfer->getKey()} when trying sending payout email with message {$exception->getMessage()}");
        }
    }
}
