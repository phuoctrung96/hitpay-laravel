<?php

namespace HitPay\Stripe\CustomAccount;

use App\Business;
use App\Enumerations\Business\Type as BusinessType;
use App\Providers\AppServiceProvider;
use Closure;
use Exception;
use HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException;
use HitPay\Stripe\CustomAccount\Exceptions\GeneralException;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use Illuminate\Support\Collection;
use Stripe;

trait Helper
{
    protected string $businessId;

    protected Business $business;

    protected ?Stripe\Account $stripeAccount = null;

    protected bool $stripeAccountLoaded = false;

    protected ?Business\PaymentProvider $businessPaymentProvider = null;

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
        if (!in_array($business->getStripeAccountBusinessType(), [ BusinessType::COMPANY, BusinessType::INDIVIDUAL ])) {
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
            throw new InvalidStateException('The "custom connected" payment provider could not be found.');
        }

        return $this->businessPaymentProvider;
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
     * @param  \Closure  $callback
     *
     * @return bool
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function isCustomAccountVerified(bool $refresh = false, bool $strict = true, Closure $callback = null) : bool
    {
        // Below are the statuses of Stripe custom account, and its description. Basically all our connected account
        // can accept payment event with `charge_enabled === false` because we are charging on behalf of them.
        //
        // Complete
        // --------
        // This account has provided the required information to fully onboard onto Stripe. They can accept payments
        // and receive payouts.
        //
        // Enabled
        // -------
        // This account has provided enough information to process payments and receive payouts. More information
        // will eventually be required when they process enough volume.
        //
        // INFORMATION EVENTUALLY NEEDED
        //
        // Restricted soon
        // ---------------
        // Provide additional information in order to keep this account in good standing.
        //
        // INFORMATION NEEDED - DUE IN 2 MONTHS
        //
        // Restricted
        // ----------
        // Provide more information in order to enable payouts for this account.
        //
        // INFORMATION NEEDED - DUE NOW
        // INFORMATION NEEDED - DUE IN 2 MONTHS
        //
        // Rejected
        // --------
        // This account was rejected by HitPay Payment Solutions Pte Ltd.
        //

        $customAccount = $this->getCustomAccount($refresh, $strict);

        // Let's assume "no due === verified", we will check the one in 'requirements' only, ignore the
        // 'future_requirements' for the time being.
        //

        $requirements = $customAccount->requirements;

        // This string describes why the account is disabled. Possible values:
        //
        // `requirements.past_due`, `requirements.pending_verification`, `listed`, `platform_paused`, `rejected.fraud`,
        // `rejected.listed`, `rejected.terms_of_service`, `rejected.other`, `under_review`, or `other`.
        //
        if ($requirements->disabled_reason !== null) {
            return false;
        }

        if (!$customAccount->tos_acceptance->date === null) {
            return false;
        }

        // Fields that was not collected by current_deadline. These fields need to be collected to enable the account.
        //
        if (count($requirements->past_due) > 0) {
            return false;
        }

        // Fields that need to be collected to keep the account enabled. If not collected by `current_deadline`,
        // these fields appear in `past_due` as well, and the account is disabled.
        //
        if (count($requirements->currently_due) > 0) {
            // "$requirements->errors" => []
            //   -  Fields that are `currently_due` and need to be collected again because validation or verification
            //      failed.
            //
            // When we are going to implement the "$callback", we can include the deadline as well.
            //
            // "$requirements->current_deadline" => null | timestamp
            //   -  Date by which the fields in `currently_due` must be collected to keep the account enabled. These
            //      fields may disable the account sooner if the next threshold is reached before they are collected.
            //
            return false;
        }

        // Fields that need to be collected assuming all volume thresholds are reached. As they become required, they
        // appear in `currently_due` as well, and `current_deadline` becomes set.
        //
        if (count($requirements->eventually_due) > 0) {
            return false;
        }

        // Fields that may become required depending on the results of verification or review. Will be an empty array
        // unless an asynchronous verification is pending. If verification fails, these fields move to
        // `eventually_due`, `currently_due`, or `past_due`.
        //
        if (count($requirements->pending_verification) > 0) {
            return false;
        }

        if (!$customAccount->charges_enabled) {
            return false;
        }

        if (!$customAccount->payouts_enabled) {
            return false;
        }

        return true;
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
