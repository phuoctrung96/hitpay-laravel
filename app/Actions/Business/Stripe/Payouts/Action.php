<?php

namespace App\Actions\Business\Stripe\Payouts;

use App\Actions\Action as BaseAction;
use App\Actions\Exceptions\BadRequest;
use App\Actions\UseLogViaStorage;
use App\Business;
use App\Enumerations\PaymentProviderAccountType;
use App\Jobs\Business\Stripe\Payout\GenerateCsv;
use App\Logics\ConfigurationRepository;
use App\Providers\AppServiceProvider;
use Exception;
use HitPay\Data\Countries;
use HitPay\Data\PaymentProviders;
use Illuminate\Support\Facades;
use Stripe;

abstract class Action extends BaseAction
{
    use UseLogViaStorage;

    protected Business $business;

    protected string $businessId;

    protected Countries\Objects\PaymentProvider $paymentProvider;

    protected string $expectedBusinessTransferId;

    protected string $paymentProviderCode;

    protected Stripe\Payout $stripePayout;

    protected Stripe\Account $stripeAccount;

    protected ?Business\Transfer $businessTransfer;

    private string $stripeAccountId;

    private string $stripePayoutId;

    /**
     * Setup with the given payout.
     *
     * @param  string  $paymentProviderCode
     * @param  string  $stripeAccountId
     * @param  string  $stripePayoutId
     *
     * @return $this
     * @throws \App\Actions\Exceptions\BadRequest
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function payout(string $paymentProviderCode, string $stripeAccountId, string $stripePayoutId)
    {
        $this->stripeAccountId = $stripeAccountId;
        $this->stripePayoutId = $stripePayoutId;

        $this->paymentProvider = PaymentProviders::all()
            ->where('official_code', 'stripe')
            ->where('code', $paymentProviderCode)
            ->first();

        $this->paymentProviderCode = $this->paymentProvider->code;

        $stripeConfigs = Facades\Config::get("services.stripe.{$this->paymentProvider->getCountry()}");

        if (!isset($stripeConfigs['secret']) || blank($stripeConfigs['secret'])) {
            throw new Exception("The configuration for Stripe '{$this->paymentProvider->getCountry()}' is not set.");
        }

        Stripe\Stripe::setApiKey($stripeConfigs['secret']);

        $this->stripeAccount = Stripe\Account::retrieve($this->stripeAccountId, [
            'stripe_version' => AppServiceProvider::STRIPE_VERSION,
        ]);

        $this->identifyBusiness();

        $this->stripePayout = Stripe\Payout::retrieve($this->stripePayoutId, [
            'stripe_account' => $this->stripeAccountId,
            'stripe_version' => AppServiceProvider::STRIPE_VERSION,
        ]);

        $this->identifyBusinessTransfer();

        $this->now = Facades\Date::now();

        $this->setLogDirectories('payment_providers', $this->paymentProviderCode, 'connect-payouts');
        $this->setLogFilename("{$this->business->getKey()}-{$this->stripePayout->id}.txt");

        $this->log($this->stripePayout->toJSON());

        return $this;
    }

    /**
     * Try to identify business by payment provider, using the Stripe account ID and custom account only.
     *
     * @return void
     * @throws \App\Actions\Exceptions\BadRequest
     */
    private function identifyBusiness() : void
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
            throw new BadRequest(
                "The business (Stripe Account ID : {$this->stripeAccountId}) isn't found when trying to identify the business for the Stripe payout (Stripe Payout ID : {$this->stripePayoutId}).",
                true
            );
        }

        $this->business = $business;
        $this->businessId = $this->business->getKey();
    }

    private function identifyBusinessTransfer() : void
    {
        $this->businessTransfer = $this->business->transfers()
            ->where('payment_provider', $this->paymentProvider->code)
            ->where('payment_provider_account_id', $this->stripeAccountId)
            ->where('payment_provider_transfer_id', $this->stripePayout->id)
            ->first();
    }

    /**
     * @return void
     * @throws \Stripe\Exception\ApiErrorException
     */
    protected function createBusinessTransfer() : void
    {
        $payoutCreatedAt = Facades\Date::createFromTimestamp($this->stripePayout->created);

        $this->businessTransfer = new Business\Transfer;

        $this->businessTransfer->payment_provider = $this->paymentProvider->code;
        $this->businessTransfer->payment_provider_account_id = $this->stripeAccountId;
        $this->businessTransfer->payment_provider_transfer_id = $this->stripePayout->id;
        $this->businessTransfer->payment_provider_transfer_method = $this->stripePayout->object;
        $this->businessTransfer->payment_provider_transfer_type = $this->paymentProvider->official_code;
        $this->businessTransfer->currency = $this->stripePayout->currency;
        $this->businessTransfer->amount = $this->stripePayout->amount;
        $this->businessTransfer->remark = "HitPay Payouts for {$payoutCreatedAt->toDateString()}";
        $this->businessTransfer->status = $this->stripePayout->status;

        $this->businessTransfer->data = [
            'stripe' => [
                'payout' => $this->stripePayout->toArray(),
            ],
            'statuses' => [
                [
                    'status' => $this->stripePayout->status,
                    'failure_code' => $this->stripePayout->failure_code,
                    'failure_message' => $this->stripePayout->failure_message,
                    'updated_at' => Facades\Date::now()->getTimestamp(),
                ],
            ],
        ];

        $this->business->transfers()->save($this->businessTransfer);

        Stripe\Payout::update($this->stripePayoutId, [
            'metadata' => [
                'platform' => Facades\Config::get('app.name'),
                'version' => ConfigurationRepository::get('platform_version'),
                'environment' => Facades\Config::get('app.env'),
                'business_id' => $this->businessId,
                'business_transfer_id' => $this->businessTransfer->getKey(),
            ],
        ], [
            'stripe_account' => $this->stripeAccountId,
            'stripe_version' => AppServiceProvider::STRIPE_VERSION,
        ]);
    }

    /**
     * @return void
     */
    protected function syncBusinessTransfer() : void
    {
        $this->businessTransfer->status = $this->stripePayout->status;

        $data = $this->businessTransfer->data;

        $statuses = $data['statuses'] ?? [];

        $statuses[] = [
            'status' => $this->stripePayout->status,
            'failure_code' => $this->stripePayout->failure_code,
            'failure_message' => $this->stripePayout->failure_message,
            'updated_at' => Facades\Date::now()->getTimestamp(),
        ];

        $data['statuses'] = $statuses;

        $this->businessTransfer->data = $data;

        $this->businessTransfer->save();

        if ($this->businessTransfer->status === 'paid' && !isset($this->businessTransfer->data['file'])) {
            GenerateCsv::dispatch($this->businessTransfer);
        }
    }
}
