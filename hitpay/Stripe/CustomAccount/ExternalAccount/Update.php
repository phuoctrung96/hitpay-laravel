<?php

namespace HitPay\Stripe\CustomAccount\ExternalAccount;

use App\Models\Business\BankAccount;
use Illuminate\Support\Facades;
use Stripe;
use Throwable;

class Update extends ExternalAccount
{
    /**
     * Update an external account for custom account.
     *
     * @param  \App\Models\Business\BankAccount  $bankAccount
     * @param  bool  $defaultForCurrency
     *
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function handle(BankAccount $bankAccount, bool $defaultForCurrency = false)
    {
        $this->getCustomAccount();

        $externalAccountParameters = [
            'account_holder_name' => $bankAccount->holder_name,
            'account_holder_type' => $bankAccount->holder_type,
        ];

        if ($defaultForCurrency) {
            $externalAccountParameters['default_for_currency'] = true;
        }

        $externalAccount = Stripe\Account::updateExternalAccount(
            $this->stripeAccount->id,
            $bankAccount->stripe_external_account_id,
            $externalAccountParameters,
            [ 'stripe_version' => $this->stripeVersion ]
        );

        try {
            // We will give a 3 times chance to sync the Stripe external account information to our database. If
            // 3 times also failed, the error will be thrown and before that, we will delete the external account.
            //
            Facades\DB::transaction(function () use ($bankAccount, $externalAccount) {
                $this->sync($bankAccount, $externalAccount);
            });
        } catch (Throwable $throwable) {
            $throwableClassName = get_class($throwable);

            Facades\Log::critical("An exception '{$throwableClassName}' was thrown when trying to update the data of newly created Stripe external account (ID : {$externalAccount->id}) to the bank account. Affected business ID : {$this->businessId}");

            throw $throwable;
        }
    }
}
