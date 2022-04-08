<?php

namespace App\Actions\Business\Stripe\Account;

use App\Enumerations\OnboardingStatus;
use App\Notifications\Business\Stripe\NotifyAccountUnverified;
use HitPay\Stripe\CustomAccount\Sync;
use HitPay\Stripe\Customer;
use Illuminate\Support\Collection;
use Stripe\Account;

class SyncFromWebhook extends Action
{
    /**
     * @return void
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function process()
    {
        if ($this->paymentProvider->payment_provider_account_type === 'custom') {
            Sync::new($this->business->payment_provider)
                ->setBusiness($this->business)->handle(null, false);

            if ($this->paymentProvider->onboarding_status === OnboardingStatus::SUCCESS) {
                $account = $this->paymentProvider->data['account'];

                $payoutsEnabled = $account['payouts_enabled'];

                if (!$payoutsEnabled) {
                    $requirements = $account['requirements'];

                    $pastDue = $requirements['past_due'];
                    $currentlyDue = $requirements['currently_due'];
                    $eventuallyDue = $requirements['eventually_due'];

                    $errorLists = Collection::make(array_unique(array_merge($pastDue, $currentlyDue, $eventuallyDue)));

                    if (
                        $errorLists->count() > 0 &&
                        $this->paymentProvider->stripe_init === 1
                    ) {
                        $this->paymentProvider->save();

                        // this mean onboarding status success but in while the account to be unverified
                        $this->business->notify(new NotifyAccountUnverified($this->paymentProvider));
                    }
                }
            }
        } else {
            Customer::new($this->data['payment_provider']);

            $account = Account::retrieve($this->stripeAccountId);

            $this->paymentProvider->data = $account->toArray();
            $this->paymentProvider->save();
        }
    }
}
