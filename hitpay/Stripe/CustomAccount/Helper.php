<?php

namespace HitPay\Stripe\CustomAccount;

use App\Business;
use App\Enumerations\Business\Type as BusinessType;
use App\Providers\AppServiceProvider;
use Exception;
use HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException;
use HitPay\Stripe\CustomAccount\Exceptions\GeneralException;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Stripe;

trait Helper
{
    protected string $businessId;

    protected Business $business;

    protected ?Stripe\Account $stripeAccount = null;

    protected bool $stripeAccountLoaded = false;

    protected ?Business\PaymentProvider $businessPaymentProvider = null;

    protected ?Business\Verification $businessVerification;

    protected string $stripeVersion = AppServiceProvider::STRIPE_VERSION;

    protected string $clientIp;

    protected string $clientUserAgent;

    /**
     * Set the business.
     *
     * @param  \App\Business  $business
     *
     * @return $this
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     */
    public function setBusiness(Business $business) : self
    {
        $this->businessId = $business->getKey();

        if ($this->paymentProvider !== $business->payment_provider) {
            throw $this->exception('The payment provider of the business must match with the requested payment provider.');
        }

        // Stripe accepts business types of `individual`, `company`, `non_profit`, and `government_entity` (US only),
        // but HitPay focuses only on the below selected type.
        //
        if (!in_array($business->business_type, [ BusinessType::COMPANY, BusinessType::INDIVIDUAL ])) {
            throw $this->exception('The business type must be a `company` or an `individual` to use custom account.');
        }

        // Custom connect is only available for the countries which HitPay has the licences.
        //
        if (!array_key_exists($business->country, static::$countries)) {
            $countries = Collection::make(static::$countries)->map(function (array $config, string $country) : string {
                return get_country_name($country) ?: '"'.strtoupper($country).'"';
            })->join(', ', ' or ');

            throw $this->exception("The business must in {$countries} to use custom account.");
        }

        $this->business = $business;

        $this->initializePaymentProvider();

        return $this;
    }

    /**
     * Get the business.
     *
     * @return \App\Business
     */
    public function getBusiness() : Business
    {
        return $this->business;
    }

    /**
     * Initialize the payment provider of the business.
     *
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     */
    private function initializePaymentProvider() : void
    {
        /**
         * We return first value only, because the businesses can't have same payment provider at the same time, so
         * if a payment provider is returned here, it must be relating Stripe.
         *
         * @var \App\Business\PaymentProvider|null $businessPaymentProvider
         */
        $businessPaymentProvider = $this->business->activePaymentProviders
            ->where('payment_provider', $this->business->payment_provider)
            ->first();

        if ($businessPaymentProvider && $businessPaymentProvider->payment_provider_account_type !== 'custom') {
            throw $this->invalidStateException('The business is still using a Stripe standard connected payment provider, disconnect that payment provider to proceed.');
        }

        $this->businessPaymentProvider = $businessPaymentProvider;
    }

    /**
     * Get the "custom connected" payment provider of the business.
     *
     * @param  bool  $strict
     *
     * @return \App\Business\PaymentProvider|null
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     */
    public function getPaymentProvider(bool $strict = true) : ?Business\PaymentProvider
    {
        if ($strict && !( $this->businessPaymentProvider instanceof Business\PaymentProvider )) {
            throw new InvalidStateException('"The "custom connected" payment provider could not be found."');
        }

        return $this->businessPaymentProvider;
    }

    /**
     * Initialize the verification of the business.
     *
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     */
    private function initializeVerification() : void
    {
        /**
         * We always get the latest verification only.
         *
         * @var \App\Business\Verification|null $businessVerification
         */
        $businessVerification = $this->business->verifications()->latest()->first();

        // TODO - 20211116 - KEEP IN VIEW by Bankorh
        //   ----------------------------------------->>>
        //   I noticed that the verification model has been changed, "soft delete" has been implemented and this
        //   might affect the verification accuracy.
        //
        if (!( $businessVerification instanceof Business\Verification )) {
            throw $this->exception('The verification could not be found.');
        }

        // We do a simple mapping here. Currently, in the `business_verification` table, the types are `business` and
        // `personal`, while in the `businesses` table, the types are `company` and `individual`.
        //
        $businessVerificationType =
            [
                'business' => 'company',
                'personal' => 'individual',
            ][$businessVerification->type] ?? $businessVerification->type;

        if ($this->business->business_type !== $businessVerificationType) {
            throw $this->invalidStateException('The business type and the business verification type are not match.');
        }

        if (!$businessVerification->isVerified()) {
            throw $this->invalidStateException('The verification of the business is incomplete.');
        }

        $this->businessVerification = $businessVerification;
    }

    /**
     * Get the verification of the business.
     *
     * @return \App\Business\Verification
     */
    public function getVerification() : Business\Verification
    {
        return $this->businessVerification;
    }

    /**
     * Get the custom account of the business from Stripe.
     *
     * @param  bool  $refresh
     * @param  bool  $strict
     *
     * @return \Stripe\Account|null
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Exception
     */
    public function getCustomAccount(bool $refresh = false, bool $strict = true) : ?Stripe\Account
    {
        $paymentProvider = $this->getPaymentProvider();

        if (!$this->stripeAccountLoaded || $refresh) {
            $this->stripeAccount = Stripe\Account::retrieve(
                $paymentProvider->payment_provider_account_id,
                ['stripe_version' => $this->stripeVersion]
            );
            $this->stripeAccountLoaded = true;
        }

        if ($this->stripeAccount instanceof Stripe\Account) {
            if ($this->stripeAccount->type !== 'custom') {
                throw new Exception("The retrieved account (Stripe ID : {$this->stripeAccount->id}) isn't a custom account, instead '{$this->stripeAccount->type}'.");
            } elseif ($this->businessId && $this->businessId !== $this->stripeAccount->metadata->business_id) {
                throw new Exception("The retrieved account (Stripe ID : {$this->stripeAccount->id}, Metadata Detected Business ID : {$this->stripeAccount->metadata->business_id}) isn't matching the business (ID : {$this->businessId}).");
            }
        } elseif ($strict) {
            throw new AccountNotFoundException("The custom account (Stripe ID : {$paymentProvider->payment_provider_account_id}) for custom account could not be found.");
        }

        return $this->stripeAccount;
    }

    /**
     * Check if the custom account is verified.
     *
     * @param  bool  $refresh
     * @param  bool  $strict
     *
     * @return bool
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function isCustomAccountVerified(bool $refresh = false, bool $strict = true) : bool
    {
        try {
            $customAccount = $this->getCustomAccount($refresh, $strict);
        } catch (AccountNotFoundException $exception) {
            if ($strict) {
                throw $exception;
            }

            return false;
        }

        if (array_key_exists('legal_entity', $customAccount->toArray())) {
            return $customAccount->toArray()['legal_entity']['verification']['status'] === 'verified';
        } elseif (array_key_exists('verification', $customAccount->toArray())) {
            return $customAccount->toArray()['verification']['status'] === 'verified';
        } elseif (
            array_key_exists('payouts_enabled', $customAccount->toArray()) &&
            array_key_exists('charges_enabled', $customAccount->toArray())
        ) {
            $payoutEnabled = $customAccount->toArray()['payouts_enabled'];
            $chargesEnabled = $customAccount->toArray()['charges_enabled'];

            if ($payoutEnabled && $chargesEnabled) {
                return true;
            }

            return false;
        } else {
            return false;
        }
    }

    /**
     * Create new exception instance, with appending business ID to the message.
     *
     * @param  string  $message
     * @param  string  $exceptionClass
     *
     * @return \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException|\HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     */
    protected function exception(string $message, string $exceptionClass = GeneralException::class) : GeneralException
    {
        $additionalData = Collection::make([
            "Related Business ID : {$this->businessId}",
        ]);

        $exceptionClassToBeUsed = GeneralException::class;

        if ($exceptionClass !== $exceptionClassToBeUsed) {
            if (!class_exists($exceptionClass)) {
                $additionalData->push("WARNING : The given exception class '{$exceptionClass}' does not exist.");
            } elseif (!is_subclass_of($exceptionClass, $exceptionClassToBeUsed)) {
                $additionalData->push("WARNING : The given exception class '{$exceptionClass}' does not inherit class '{$exceptionClassToBeUsed}'.");
            } else {
                $exceptionClassToBeUsed = $exceptionClass;
            }
        }

        return new $exceptionClassToBeUsed("{$message} ({$additionalData->join(' / ')})");
    }

    /**
     * Helper to create new `InvalidStateException`.
     *
     * @param  string  $message
     *
     * @return \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     */
    protected function invalidStateException(string $message) : InvalidStateException
    {
        return $this->exception($message, InvalidStateException::class);
    }

    /**
     * Check custom account allowed to updating or not
     * @return bool
     */
    protected function isUpdateDataAllowed() : bool
    {
        // https://stripe.com/docs/connect/update-verified-information
        if (!$this->stripeAccount instanceof Stripe\Account) {
            $this->stripeAccount = $this->getCustomAccount(true);
        }

        $currentStripeAccount = $this->stripeAccount;

        $tosAcceptance = $currentStripeAccount['tos_acceptance'];

        if ($tosAcceptance['date'] === "") {
            return true;
        }

        if ($this->businessPaymentProvider->stripe_init === 0) {
            return true;
        }

        $account = $this->businessPaymentProvider->data['account'];

        $charges_enabled = $account['charges_enabled'];

        $payouts_enabled = $account['payouts_enabled'];

        if ($charges_enabled && $payouts_enabled) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param string $clientIp
     * @return $this
     */
    public function setClientIp(string $clientIp) : self
    {
        $this->clientIp = $clientIp;

        return $this;
    }

    /**
     * @param string $clientUserAgent
     * @return $this
     */
    public function setClientUserAgent(string $clientUserAgent): self
    {
        $this->clientUserAgent = $clientUserAgent;

        return $this;
    }
}
