<?php

namespace HitPay\Stripe\CustomAccount;

use App\Business\PaymentProvider;
use App\Business\Verification;
use App\Enumerations\Business\Type;
use App\Enumerations\CountryCode;
use App\Enumerations\OnboardingStatus;
use App\Helpers\StripeCustomAccountHelper;
use Exception;
use HitPay\Stripe\Core;
use HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades;
use Illuminate\Support\Str;
use Stripe;

abstract class CustomAccount extends Core
{
    use Helper, StripeCustomAccountHelper;

    /**
     * Generate the data of the business for Stripe, based on account type.
     *
     * @return array
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     */
    protected function generateData() : array
    {
        // TODO - There's an issue with the verification, we can't simply use MyInfo verification for custom account
        //   verification because of the type, our platform allows company to be identified using individual account.

        switch ($this->business->getStripeAccountBusinessType()) {
            case Type::COMPANY:
                $additionalData = $this->generateCompanyData();

                break;

            case Type::INDIVIDUAL:
                $additionalData = $this->generateIndividualData();

                break;

            default:
                throw $this->exception('The business type must be a company or an individual to generate data.');
        }

        // We set prefix only when the business name is more than 2 characters. (Card charges only)
        //
        // Example in our Stripe settings:
        //
        // Statement Descriptor : HitPay Singapore
        // Shortened Descriptor : HitPay
        //
        // If the statement descriptor on card charges is set and a prefix (shortened descriptor) is not set, Stripe
        // truncates the account statement descriptor as needed to set the prefix value. If the account statement
        // descriptor contains fewer than 10 characters, it is not truncated.
        //
        // So, for example, if the prefix is 'HitPay' (6 characters), the dynamic suffix can contain up to 14
        // characters – for example, '9-22-19 10K' (11 characters) or 'OCT MARATHON' (12 characters). The computed
        // statement descriptor would then be 'HitPay* 9-22-19 10K' or 'HitPay* OCT MARATHON'.
        //
        // Read more : https://stripe.com/docs/statement-descriptors
        //
        // TODO - 20211117 - KEEP IN VIEW by Bankorh
        //   ----------------------------------------->>>
        //   We are using destination charge, I think the charge statement descriptor will be HitPay instead of the
        //   one set in custom account. We will have to figure out this, if not we will have to set the custom account
        //   statement descriptor as suffix
        //
        if (Str::length($this->business->statement_description) >= 2) {
            $businessName = strtoupper($this->business->statement_description);
            $businessName = Str::limit($businessName, 10, '');

            $statementDescriptorPrefix = $businessName;
        }

        if ($this->business->website && Facades\URL::isValidUrl($this->business->website)) {
            $websiteUrl = $this->business->website;
        } else {
            $websiteUrl = Facades\URL::route('shop.business', $this->business->identifier ?: $this->business->getKey());
        }

        $merchantCategory = $this->business->merchantCategory()->first();

        $basicData = [
            'email' => $this->business->email,
            'business_profile' => [
                'name' => $this->business->name,   // Optional.  - The customer-facing business name.
                'url' => $websiteUrl,              // Mandatory. - The business’s publicly available website.
                'mcc' => $this->validateMerchantCategoryCode($merchantCategory->code),
            ],
            'settings' => [
                'card_payments' => [
                    'statement_descriptor_prefix' => $statementDescriptorPrefix ?? null,
                ],
            ],
        ];

        return array_merge($basicData, $additionalData);
    }

    private function generateCompanyData() : array
    {
        $companyParams = [
            'name' => $this->business->name,
            'address' => [
                'line1' => $this->business->street,
                'city' => $this->business->city,
                'state' => $this->business->state,
                'postal_code' => $this->business->postal_code,
            ],
            'phone' => $this->business->phone_number,
        ];

        $businessVerification = $this->business->verifications()->latest()->first();

        if ($businessVerification instanceof Verification) {
            // We will still update this everytime. If the charge or payout failed, let the user update it again.
            //
            $identification = (string) $businessVerification->identification;

            $companyParams['registration_number'] = $this->validateIdNumber($identification);
            $companyParams['tax_id'] = $this->validateTaxId($identification);
            $companyParams['vat_id'] = $this->validateIdNumber($identification);
        }

        return [
            'company' => $companyParams,
        ];
    }

    private function generateIndividualData() : array
    {
        $params = [
            'company' => [
                'name' => $this->business->name,
                'address' => [
                    'line1' => $this->business->street,
                    'city' => $this->business->city,
                    'state' => $this->business->state,
                    'postal_code' => $this->business->postal_code,
                ],
                'phone' => $this->business->phone_number,
            ],
        ];

        $businessVerification = $this->business->verifications()->latest()->first();

        if ($businessVerification instanceof Verification) {
            // The data for individual type is a bit different with the business type. The person for individual is
            // in the custom account itself. Anyway, we will update the ID everytime. If the charge or payout failed,
            // let the user update it again using account link.
            //
            $individualParams = [
                'id_number' => $this->validateIdNumber($businessVerification->identification),
            ];

            if ($this->businessPaymentProvider->stripe_init === 0) {
                $person = $businessVerification->getPersonsForStripe()[0] ?? null;

                if (is_array($person)) {
                    $individualParams = array_merge($individualParams, $person);
                }

                $this->businessPaymentProvider->stripe_init = 1;
                $this->businessPaymentProvider->save();
            }

            $params['individual'] = $individualParams;
        }

        return $params;
    }

    /**
     * Generate the desired capabilities for custom account.
     *
     * (Link 1) https://stripe.com/docs/connect/required-verification-information
     *
     *     Select
     *         "Country"           => "Singapore";
     *         "Service Agreement" => "Full";
     *         "Business Type"     => "Company" / "Individual"
     *     Then
     *         Check the available capabilities
     *
     * (Link 2) https://stripe.com/docs/api/accounts/create#create_account-capabilities
     *
     * The capabilities in the array below are those in the options of the (link 1). And the remaining listed here in
     * the comment here are those found in (link 2).
     *
     *     - `acss_debit_payments`         - Selectable in (link 1) but not available in Singapore
     *     - `au_becs_debit_payments`      - Selectable in (link 1) but not available in Singapore
     *     - `bacs_debit_payments`         - Selectable in (link 1) but not available in Singapore
     *     - `boleto_payments`             - Not available in Singapore
     *     - `cartes_bancaires_payments`   - Selectable in (link 1) but not available in Singapore
     *     - `jcb_payments`                - Selectable in (link 1) but not available in Singapore
     *     - `klarna_payments`             - Selectable in (link 1) but not available in Singapore
     *     - `oxxo_payments`               - Selectable in (link 1) but not available in Singapore
     *     - `tax_reporting_us_1099_k`     - U.S. tax reporting for Connect platforms
     *     - `tax_reporting_us_1099_misc`  - U.S. tax reporting for Connect platforms
     *
     * @return array
     */
    protected function generateDesiredCapabilities() : array
    {
        if ($this->business->country == CountryCode::SINGAPORE) {
            $capabilities = [
                'bancontact_payments',
                'card_payments',
                'eps_payments',
                'giropay_payments',
                'grabpay_payments',
                'ideal_payments',
                'p24_payments',
                'sepa_debit_payments',
                'sofort_payments',
                'transfers'
            ];
        }

        if ($this->business->country == CountryCode::MALAYSIA) {
            // The `fpx_payments` capability is not requestable for Individual or Sole Proprietor accounts. Not sure
            // how does it affect us collect the payment on behalf. Have to test.
            //
            $capabilities = [
                'card_payments',
                // 'fpx_payments',
                'grabpay_payments',
                'transfers'
            ];
        }

        return Collection::make($capabilities)->mapWithKeys(function (string $value) {
            return [ $value => [ 'requested' => true ] ];
        })->toArray();
    }

    /**
     * Sync the information of our user's business account to Stripe, then get the latest account data from Stripe and
     * store it to the related payment provider.
     *
     * @param  array  $data
     *
     * @return \App\Business\PaymentProvider
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    protected function updateAccount(array $data) : PaymentProvider
    {
        $this->getCustomAccount();

        sleep(2);

        try {
            $this->stripeAccount = Stripe\Account::update(
                $this->stripeAccount->id,
                $data,
                [ 'stripe_version' => $this->stripeVersion ]
            );
        } catch (Exception $exception) {
            Facades\Log::critical('error on business '.$this->businessId.' with data: ' . json_encode($data));

            throw $exception;
        }

        $this->syncAccount();

        return $this->businessPaymentProvider;
    }

    /**
     * Sync the Stripe custom account data and information to the payment provider instance.
     *
     * @return \App\Business\PaymentProvider
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    protected function syncAccount() : PaymentProvider
    {
        $customAccount = $this->getCustomAccount();

        $businessPaymentProviderIsAlreadyReady = $this->businessPaymentProvider->payment_provider_account_ready;

        $this->businessPaymentProvider->payment_provider_account_ready = $this->isCustomAccountVerified();

        if ($this->businessPaymentProvider->payment_provider_account_ready) {
            // no need update onboarding_status become pending after success if the account is unverified
            // because this onboarding_status will be checked for handle unverified account triggered from webhook too.
            $this->businessPaymentProvider->onboarding_status = OnboardingStatus::SUCCESS;
        } elseif ($businessPaymentProviderIsAlreadyReady) {
            // If the status of the Stripe account change from ready to not ready, we log it.
            //
            Facades\Log::critical(
                "The status of the payment provider (Code : {$this->businessPaymentProvider->payment_provider}), for business (ID : {$this->businessId}) has changed from ready to not ready."
            );
        }

        $paymentProviderData = $this->businessPaymentProvider->data;

        $paymentProviderData['account'] = $customAccount->toArray();

        $this->businessPaymentProvider->data = $paymentProviderData;

        // Same with update, we will give a 3 times chance to sync the Stripe account information to our database. If
        // 3 times also failed, the error will be thrown and then only that time we will decide what to do next.
        //
        Facades\DB::transaction(function () {
            $this->businessPaymentProvider->save();
        }, 3);

        return $this->businessPaymentProvider;
    }

    /**
     * Generate Stripe Account Link.
     *
     * Account Links are the means by which a "Connect" platform grants a connected account permission to access
     * Stripe-hosted applications, such as Connect Onboarding.
     *
     * Read more : https://stripe.com/docs/api/account_links
     *
     * @param  string  $type
     *
     * @return string
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Stripe\Exception\ApiErrorException
     */
    protected function generateCustomAccountLink(string $type) : string
    {
        try {
            $this->getCustomAccount();
        } catch (InvalidStateException | AccountNotFoundException $exception) {
            throw $this->exception($exception->getMessage(), get_class($exception));
        }

        // $type
        // -----
        // `account_onboarding` - Send the user to the form in this mode to just collect the new information we need.
        // `account_update`     - Displays the fields that are already populated on the account object, and allows your
        //                        user to edit previously provided information. Consider framing this as “edit my
        //                        profile” or “update my verification information”.

        if (!in_array($type, [ 'account_onboarding', 'account_update' ])) {
            throw $this->exception("The type '{$type}' is invalid for generating account link.");
        }

        // 'collect'
        // ---------
        // `currently_due`  - Request only the user information needed for verification at this specific point in time.
        // `eventually_due` - Include a more complete set of questions that we’ll eventually need to collect.

        $refreshUrl = Facades\URL::route(
            'dashboard.business.settings.payment-providers.platform.custom-account.redirect',
            $this->business->getKey()
        );

        $state = Str::random();

        Facades\Cache::set($this->generateSyncStateCacheKey($state), true, Facades\Date::now()->addMinutes(30));

        $returnUrl = Facades\URL::route(
            'dashboard.business.settings.payment-providers.platform.custom-account.callback',
            [
                'business_id' => $this->business->getKey(),
                'state' => $state,
            ]
        );

        $accountLink = Stripe\AccountLink::create([
            'account' => $this->stripeAccount->id,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => $type,
            'collect' => 'eventually_due',
        ], [
            'stripe_version' => $this->stripeVersion,
        ]);

        return $accountLink->url;
    }

    /**
     * Get the cache key to identify the sync state for Stripe account link.
     *
     * @param  string  $state
     *
     * @return string
     */
    protected function generateSyncStateCacheKey(string $state) : string
    {
        return "business_{$this->businessId}:custom_account_sync_{$state}";
    }
}
