<?php

namespace HitPay\Stripe\CustomAccount;

use App\Business\Verification;
use App\Jobs\Business\Stripe\Person\Push;
use Exception;
use Illuminate\Support\Facades;

class Update extends CustomAccount
{
    /**
     * Basically we are syncing the information of our user's business account to Stripe, then get the latest account
     * data from Stripe and store it to the related payment provider.
     *
     * @param  bool  $useStripeUpdate
     *
     * @return \App\Business\PaymentProvider|\Illuminate\Http\RedirectResponse
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function handle(bool $useStripeUpdate = false)
    {
        if ($useStripeUpdate) {
            return Facades\Response::redirectTo($this->generateCustomAccountLink('account_update'));
        }

        $updated = $this->updateAccount($this->generateData());

        // We create the person here for non-individual business ONLY. For individual, it has been handled during
        // update account, in previous line.
        //
        if ($this->business->business_type !== 'individual' && $this->businessPaymentProvider->stripe_init === 0) {
            $this->createPersonUsingVerificationData();
        }

        return $updated;
    }

    private function createPersonUsingVerificationData() : void
    {
        $businessVerification = $this->business->verifications()->latest()->first();

        if (!$businessVerification instanceof Verification) {
            return;
        }

        $persons = $businessVerification->getPersonsForStripe();

        try {
            foreach ($persons as $person) {
                Push::dispatch($this->business, $person);
            }
        } catch (Exception $exception) {
            Facades\Log::info("Failed to add person to Stripe, error got: {$exception->getMessage()}");
        }

        $this->businessPaymentProvider->stripe_init = 1;
        $this->businessPaymentProvider->save();
    }
}
