<?php

namespace App\Actions\Business\Stripe\Person;

use App\Actions\Business\Action as BaseAction;
use App\Actions\Exceptions\BadRequest;
use App\Actions\UseLogViaStorage;
use App\Business;
use App\Enumerations\PaymentProviderAccountType;
use App\Providers\AppServiceProvider;
use Exception;
use HitPay\Data\PaymentProviders;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Stripe\Account;
use Stripe\Person;
use Stripe\Stripe;
use HitPay\Data\Countries;

abstract class Action extends BaseAction
{
    protected ?string $stripePersonId = null;

    protected ?string $stripeAccountId= null;

    protected Account $stripeAccount;

    protected ?string $businessId = null;

    protected ?string $hitpayPersonId = null;

    protected Countries\Objects\PaymentProvider $paymentProvider;

    protected Person $stripePerson;

    protected string $paymentProviderCode;

    protected bool $isCreationMode = false;

    use UseLogViaStorage;

    /**
     * @throws \Exception
     */
    public function person(string $paymentProviderCode, string $stripeAccountId, string $stripePersonId)
    {
        $this->stripeAccountId = $stripeAccountId;
        $this->stripePersonId = $stripePersonId;

        $this->paymentProvider = PaymentProviders::all()
            ->where('official_code', 'stripe')
            ->where('code', $paymentProviderCode)
            ->first();

        $this->paymentProviderCode = $this->paymentProvider->code;

        $stripeConfigs = Config::get("services.stripe.{$this->paymentProvider->getCountry()}");

        if (!isset($stripeConfigs['secret']) || blank($stripeConfigs['secret'])) {
            throw new Exception("The configuration for Stripe '{$this->paymentProvider->getCountry()}' is not set.");
        }

        Stripe::setApiKey($stripeConfigs['secret']);

        if (!$this->identifyBusiness()) {
            return $this;
        }

        // sometime got rate limited issue like this message:
        // This object cannot be accessed right now because
        // another API request or Stripe process is currently accessing it.
        // If you see this error intermittently, retry the request.
        // If you see this error frequently and are making multiple concurrent
        // requests to a single object, make your requests serially or at a lower rate
        sleep(3);

        try {
            $this->stripeAccount = Account::retrieve($this->stripeAccountId, [
                'stripe_version' => AppServiceProvider::STRIPE_VERSION,
            ]);

            $this->stripePerson = $this->stripeAccount->retrievePerson($this->stripeAccountId, $this->stripePersonId,[], [
                'stripe_version' => AppServiceProvider::STRIPE_VERSION
            ]);

            if (!$this->isCreationMode) {
                if (!isset($this->stripePerson->metadata->business_person_id) && $this->stripePerson->metadata->business_person_id == "") {
                    Log::critical("Warning: Stripe person not set at business person metadata with
                        account {$this->stripeAccountId} with business key {$this->business->getKey()} with
                        person id {$this->stripePersonId}");
                }

                $this->hitpayPersonId = $this->stripePerson->metadata->business_person_id;
            }

            $this->now = Date::now();

            $this->setLogDirectories('payment_providers', $this->paymentProviderCode, 'connect-person');
            $this->setLogFilename("{$this->business->getKey()}-{$stripePersonId}.txt");

            $this->log($this->stripePerson->toJSON());
        } catch (\Exception $exception) {
            Log::critical("Warning: person.created / person.deleted have issue. Error: {$exception->getMessage()} ({$exception->getFile()}:{$exception->getLine()})\n{$exception->getTraceAsString()}");
        }

        return $this;
    }


    /**
     * @return $this
     */
    public function setCreationMode() : self
    {
        $this->isCreationMode = true;

        return $this;
    }

    /**
     * Try to identify business by payment provider, using the Stripe account ID and custom account only.
     *
     * @return bool
     * @throws \App\Actions\Exceptions\BadRequest
     */
    private function identifyBusiness() : bool
    {
        $businessQuery = Business::query();

        $businessPaymentProvidersQuery = ( new Business )->paymentProviders()->getModel();

        $business = $businessQuery
            ->select($businessQuery->qualifyColumn('*'))
            ->join(
                $businessPaymentProvidersQuery->getTable(),
                $businessQuery->qualifyColumn('id'),
                $businessPaymentProvidersQuery->qualifyColumn('business_id')
            )
            ->where($businessPaymentProvidersQuery->qualifyColumn('payment_provider'), $this->paymentProviderCode)
            ->where(
                $businessPaymentProvidersQuery->qualifyColumn('payment_provider_account_id'),
                $this->stripeAccountId
            )
            ->where(
                $businessPaymentProvidersQuery->qualifyColumn('payment_provider_account_type'),
                PaymentProviderAccountType::STRIPE_CUSTOM_TYPE
            )
            ->first();

        if (!( $business instanceof Business )) {
            Log::critical("The business (Stripe Account ID : {$this->stripeAccountId}) isn't found when
                trying to identify the business for the Stripe person (Stripe Person ID : {$this->stripePersonId}).");

            return false;
        }

        $this->business = $business;
        $this->businessId = $this->business->getKey();

        return true;
    }

    /**
     * @return bool
     */
    protected function validateProcess() : bool
    {
        if (!($this->business instanceof Business)) {
            Log::critical("The business (Stripe Account ID : {$this->stripeAccountId}) isn't found when
                trying to identify the business for the Stripe person (Stripe Person ID : {$this->stripePersonId}).");

            return false;
        }

        if ($this->paymentProviderCode === "") {
            Log::critical("PaymentProviderCode null with (Stripe Account ID : {$this->stripeAccountId}) and (Stripe Person ID : {$this->stripePersonId}).");

            return false;
        }

        if ($this->stripePerson === null) {
            Log::critical("Stripe Person null with (Stripe Account ID : {$this->stripeAccountId}) and (Stripe Person ID : {$this->stripePersonId}).");

            return false;
        }

        return true;
    }
}
