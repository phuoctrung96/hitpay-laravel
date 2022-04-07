<?php

namespace App\Actions\Business\BasicDetails;

use App\Notifications\Business\Stripe\NotifyVerifyAccount;
use HitPay\Stripe\CustomAccount\Update;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UpdateStripeAccount extends Action
{
    /**
     * Update the bank account.
     *
     * @return bool
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function process() : bool
    {
        $businessPaymentProvider = $this->business->paymentProviders()->where('payment_provider', 'stripe_sg')
            ->where('payment_provider_account_type', 'custom')
            ->first();

        if ($businessPaymentProvider) {
            if ($businessPaymentProvider->payment_provider_account_ready) {
                Update::new($businessPaymentProvider->payment_provider)->setBusiness($this->business)->handle();
            } else {
                try {
                    // notify user via email
                    $this->business->notify(new NotifyVerifyAccount($this->business));
                } catch (\Exception $exception) {
                    throw new HttpException(400, 'NotifyVerifyAccount failed for this business account with message ' . $exception->getMessage());
                }
            }
        }

        return true;
    }
}
