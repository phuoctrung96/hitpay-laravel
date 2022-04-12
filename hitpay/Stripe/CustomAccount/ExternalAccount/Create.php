<?php

namespace HitPay\Stripe\CustomAccount\ExternalAccount;

use App\Enumerations\CountryCode;
use App\Logics\ConfigurationRepository;
use App\Models\Business\BankAccount;
use Illuminate\Support\Facades;
use Illuminate\Support\Str;
use Stripe;
use Throwable;

class Create extends ExternalAccount
{
    /**
     * Create an external account for custom account.
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

        if ($bankAccount->country === CountryCode::MALAYSIA
            && Str::length($bankAccount->bank_routing_number) === 11
            && Str::endsWith($bankAccount->bank_routing_number, 'XXX')) {
            $bankAccountBankRoutingNumber = substr($bankAccount->bank_routing_number, 0, 8);
        }

        $externalAccountParameters = [
            'object' => 'bank_account',
            'country' => $bankAccount->country,
            'currency' => $bankAccount->currency,
            'account_holder_name' => $bankAccount->holder_name,
            'account_holder_type' => $bankAccount->holder_type,
            'routing_number' => $bankAccountBankRoutingNumber ?? $bankAccount->bank_routing_number,
            'account_number' => $bankAccount->number,
            'metadata' => [
                'platform' => Facades\Config::get('app.name'),
                'version' => ConfigurationRepository::get('platform_version'),
                'environment' => Facades\Config::get('app.env'),
                'business_id' => $this->business->getKey(),
                'business_bank_account_id' => $bankAccount->getKey(),
            ],
        ];

        if ($defaultForCurrency) {
            $externalAccountParameters['default_for_currency'] = true;
        }

        $externalAccount = Stripe\Account::createExternalAccount(
            $this->stripeAccount->id,
            [ 'external_account' => $externalAccountParameters ],
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

            Facades\Log::critical("An exception '{$throwableClassName}' was thrown when trying to update the data of newly created Stripe external account (ID : {$externalAccount->id}) to the bank account. The Stripe external account will be deleted and the original exception will be thrown again after this message is logged. Affected business ID : {$this->businessId}");

            $externalAccount->delete();

            throw $throwable;
        }
    }
}
