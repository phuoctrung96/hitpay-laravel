<?php

namespace HitPay\Stripe\CustomAccount\ExternalAccount;

use App\Models\Business\BankAccount;
use Exception;
use Illuminate\Support\Facades;
use Stripe;
use Throwable;

class Delete extends ExternalAccount
{
    /**
     * Delete the bank account (Stripe external account) for a custom account.
     *
     * @param  \App\Models\Business\BankAccount  $bankAccount
     *
     * @return bool
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Stripe\Exception\InvalidRequestException
     * @throws \Throwable
     */
    public function handle(BankAccount $bankAccount)
    {
        if ($this->businessId !== $bankAccount->business_id) {
            throw new Exception("The business (ID : {$this->businessId}) has no right to the bank account (ID : {$bankAccount->getKey()})");
        }

        $this->justDelete($bankAccount->stripe_external_account_id);

        $bankAccount->stripe_external_account_id = null;
        $bankAccount->stripe_external_account_default = false;

        $bankAccountData = $bankAccount->data;

        unset($bankAccountData['stripe']['external_account']);

        $bankAccount->data = $bankAccountData;

        // We already deleted the external account, and we will give a 3 times chance to update the bank account
        // to our database.
        //
        Facades\DB::transaction(function () use ($bankAccount) {
            $bankAccount->save();
        });

        return true;
    }

    /**
     * Just delete the Stripe external account.
     *
     * @param  string  $stripeExternalAccountId
     *
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Stripe\Exception\InvalidRequestException
     */
    public function justDelete(string $stripeExternalAccountId) : void
    {
        $this->getCustomAccount();

        try {
            Stripe\Account::deleteExternalAccount(
                $this->stripeAccount->id,
                $stripeExternalAccountId,
                [],
                [ 'stripe_version' => $this->stripeVersion ]
            );
        } catch (Stripe\Exception\InvalidRequestException $exception) {
            // We process the exception when resource is missing only. Stripe sometimes doesn't allow us to delete
            // the external account, and we will let the exception thrown as usual. For example the error message
            // below:
            //
            //  "  You cannot delete the default external account for your default currency. Please make another
            //     external account the default using the `default_for_currency` param, and then delete this one.  "
            //
            if ($exception->getStripeCode() !== 'resource_missing') {
                throw $exception;
            }

            $exceptionClassName = get_class($exception);

            Facades\Log::critical("An exception '{$exceptionClassName}' was thrown when trying but failed to delete the Stripe external account (ID : {$stripeExternalAccountId}) of the bank account. {$exception->getError()->message}.");
        } catch (Throwable $throwable) {
            Facades\Log::critical("An exception was thrown when trying but failed to delete the Stripe external account (ID : {$stripeExternalAccountId}) of the bank account.");

            throw $throwable;
        }
    }
}
