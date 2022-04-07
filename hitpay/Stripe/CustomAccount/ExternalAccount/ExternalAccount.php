<?php

namespace HitPay\Stripe\CustomAccount\ExternalAccount;

use App\Models\Business\BankAccount;
use HitPay\Stripe\Core;
use HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException;
use HitPay\Stripe\CustomAccount\Helper;
use Stripe;

abstract class ExternalAccount extends Core
{
    use Helper;

    /**
     * Get the external account of a custom account from Stripe.
     *
     * @param  string  $id
     * @param  bool  $strict
     *
     * @return \Stripe\StripeObject|null
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getExternalAccount(string $id, bool $strict = true) : ?Stripe\StripeObject
    {
        $externalAccount = $this->getExternalAccounts()->retrieve($id);

        if ($strict && !( $externalAccount instanceof Stripe\StripeObject )) {
            throw new AccountNotFoundException("The external account (Stripe ID : {$id}) for this custom account could not be found.");
        }

        return $externalAccount;
    }

    /**
     * Get the collection of external accounts of a custom account from Stripe.
     *
     * @param  bool  $strict
     *
     * @return \Stripe\Collection
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getExternalAccounts(bool $strict = false) : Stripe\Collection
    {
        $externalAccounts = $this->getCustomAccount()->external_accounts->all();

        if ($strict && $externalAccounts->count() === 0) {
            throw new AccountNotFoundException('There are no external accounts in this custom account.');
        }

        return $externalAccounts;
    }

    /**
     * Sync Stripe external account with bank account.
     *
     * @param  \Stripe\BankAccount  $externalAccount
     * @param  string  $platform
     */
    public function sync(BankAccount $bankAccount, Stripe\BankAccount $externalAccount) : void
    {
        $bankAccount->stripe_platform = $this->paymentProvider;
        $bankAccount->stripe_external_account_id = $externalAccount->id;
        $bankAccount->stripe_external_account_default = $externalAccount->default_for_currency;

        $bankAccountData = $bankAccount->data;

        $bankAccountData['stripe']['external_account'] = $externalAccount->toArray();

        $bankAccount->data = $bankAccountData;

        $bankAccount->save();

        if ($bankAccount->stripe_external_account_default) {
            $bankAccount->business->bankAccounts()->where([
                'currency' => $bankAccount->currency,
                'country' => $bankAccount->country,
                'stripe_platform' => $bankAccount->stripe_platform,
                'stripe_external_account_default' => true,
            ])->where('id', '!=', $bankAccount->getKey())->update([
                'stripe_external_account_default' => false,
            ]);
        }
    }
}
