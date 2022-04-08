<?php

namespace HitPay\Stripe\CustomAccount;

use App\Business\PaymentProvider;
use App\Enumerations\Business\Type;
use App\Enumerations\CountryCode;
use App\Enumerations\OnboardingStatus;
use App\Helpers\StripeCustomAccountHelper;
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

        switch ($this->business->business_type) {
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
        $companyParams = [];

        $companyParams['name'] = $this->business->name;

        $businessVerification = $this->business->verifications()->latest()->first();

        if ($businessVerification) {
            if ($this->isUpdateDataAllowed()) {
                $companyParams['registration_number'] = $this->validateIdNumber((string)$businessVerification->indentification);
                $companyParams['tax_id'] = $this->validateTaxId((string)$businessVerification->indentification);
                $companyParams['vat_id'] = $this->validateIdNumber((string)$businessVerification->indentification);
            }

            $isOwnerProvided = false;
            $isDirectorProvided = false;

            $businessPersons = $businessVerification->persons()->get();

            foreach ($businessPersons as $businessPerson) {
                foreach ($businessPerson->relationship as $key => $status) {
                    if ($key == 'owner' &&  $status == true) {
                        $isOwnerProvided = true;
                    }

                    if ($key == 'director' &&  $status == true) {
                        $isDirectorProvided = true;
                    }
                }
            }

            if ($isOwnerProvided) {
                $companyParams['owners_provided'] = true;
            }

            if ($isDirectorProvided) {
                $companyParams['directors_provided'] = true;
            }

            if ($this->business->street != "" && $this->isUpdateDataAllowed()) {
                $companyParams['address']['line1'] = $this->business->street;
            }

            if ($this->isUpdateDataAllowed()) {
                $companyParams['address']['city'] = $this->business->city;
                $companyParams['address']['state'] = $this->business->state;
                $companyParams['address']['postal_code'] = $this->business->postal_code;
            }

            $companyParams['phone'] = $this->validatePhoneNumber($this->business->phone_number);

            $taxFileUploaded = $this->businessPaymentProvider->files()
                ->where('group', 'stripe_file_tax')
                ->first();

            if ($taxFileUploaded && $this->isUpdateDataAllowed()) {
                $companyParams['verification']['document']['back'] = $taxFileUploaded->stripe_file_id;
            }
        }

        return [
            'company' => $companyParams,
        ];
    }

    private function generateIndividualData() : array
    {
        $params = [];

        $individualParams = [];

        if ($this->businessPaymentProvider) {
            $persons = $this->businessPaymentProvider->persons()->get();

            if ($persons->count() > 0) {
                $person = $persons->first();

                if ($this->isUpdateDataAllowed()) {
                    $individualParams['id_number'] = $this->validateIdNumber($person->id_number);
                    $individualParams['first_name'] = $person->first_name;
                    $individualParams['last_name'] = $person->last_name;
                }

                $individualParams['email'] = $person->email;
                $individualParams['phone'] = $this->validatePhoneNumber($person->phone);

                if ($this->isUpdateDataAllowed()) {
                    $individualParams['dob'] = $this->validateDateOfBirth($person->dob);
                }

                if ($this->isUpdateDataAllowed()) {
                    $individualParams['address'] = [
                        'line1' => $this->validateAddress($person->address),
                        'postal_code' => $person->postal_code,
                        'city' => $person->city,
                        'state' => $person->state,
                    ];
                }

                $individualParams['nationality'] = strtoupper($person->country);

                $individualParams['full_name_aliases'] = [$person->alias_name];

                $params['individual'] = $individualParams;

                $companyParams = [];

                if ($this->isUpdateDataAllowed()) {
                    $companyParams['address'] = [
                        'line1' => $this->validateAddress($this->business->street),
                        'city' => $this->business->city,
                        'state' => $this->business->state,
                        'postal_code' => $this->business->postal_code,
                    ];
                }

                $companyParams['phone'] = $this->validatePhoneNumber($this->business->phone_number);

                if ($this->isUpdateDataAllowed()) {
                    $companyParams['name'] = $this->business->display_name;
                }

                $params['company'] = $companyParams;
            }
        } else {
            $businessVerification = $this->business->verifications()->latest()->first();

            if ($businessVerification) {
                $individualParams['id_number'] = $this->validateIdNumber($businessVerification->indentification);

                $businessVerificationData = $businessVerification->verificationData('submitted');

                $individualParams['first_name'] = $businessVerificationData['name'];

                $individualParams['email'] = $businessVerificationData['email'];

                $individualParams['phone'] = $this->validatePhoneNumber($this->business->phone_number);

                $individualParams['dob'] = $this->validateDateOfBirth($businessVerificationData['dob']);

                $individualParams['address'] = [
                    'line1' => $this->validateAddress($businessVerificationData['regadd'])
                ];

                $params['individual'] = $individualParams;
            }
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
                // The fpx_payments capability is not requestable for Individual or Sole Proprietor accounts. TODO - TEST
                // 'fpx_payments',
                'giropay_payments',
                'grabpay_payments',
                'ideal_payments',
                'legacy_payments',
                'p24_payments',
                'sepa_debit_payments',
                'sofort_payments',
                'transfers'
            ];
        }

        if ($this->business->country == CountryCode::MALAYSIA) {
            $capabilities = [
                'card_payments',
                'grabpay_payments',
                'legacy_payments',
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
        } catch (\Exception $exception) {
            Facades\Log::critical('error on business '.$this->businessId.' with data: ' . json_encode($data));

            throw $exception;
        }

        $this->syncAccount();

        return $this->businessPaymentProvider;
    }

    /**
     * Sync the Stripe custom account data and information to the payment provider instance.
     *
     * @throws \Throwable
     */
    protected function syncAccount() : void
    {
        $customAccount = $this->getCustomAccount();

        $this->businessPaymentProvider->payment_provider_account_ready = $this->isCustomAccountVerified();

        if ($this->businessPaymentProvider->payment_provider_account_ready) {
            // no need update onboarding_status become pending after success if the account is unverified
            // because this onboarding_status will be checked for handle unverified account triggered from webhook too.
            $this->businessPaymentProvider->onboarding_status = OnboardingStatus::SUCCESS;
        }

        $paymentProviderData = $this->businessPaymentProvider->data;

        /**
        // this one for mock error because stripe not showing when we input negative test mode.

        $data = $customAccount->toArray();
        $data['requirements']['errors'] = [
            [
                "requirement" => "company.address.line1",
                "code" => "invalid_street_address",
                "reason" => "The provided street address cannot be found. Please verify the street name and number are correct in \"10 Downing Street\"",
            ],
            [
                "requirement" => "person_4KKDF3008Fthhcl1.verification.document",
                "code" => "verification_document_failed_greyscale",
                "reason" => "Greyscale documents cannot be read. Please upload a color copy of the document.",
            ],
        ];

        $data['requirements']['currently_due'] = [
            'company.name',
            'company.address.line1',
            'person_4KKDF3008Fthhcl1.verification.document',
        ];

        $paymentProviderData['account'] = $data;

        **/

        $paymentProviderData['account'] = $customAccount->toArray();

        $this->businessPaymentProvider->data = $paymentProviderData;

        // Same with update, we will give a 3 times chance to sync the Stripe account information to our database. If
        // 3 times also failed, the error will be thrown and then only that time we will decide what to do next.
        //
        Facades\DB::transaction(function () {
            $this->businessPaymentProvider->save();
        }, 3);
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
