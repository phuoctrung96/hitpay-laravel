<?php

namespace App\Business;

use App\Enumerations\Business\Channel;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\Business\RecurringCycle;
use App\Enumerations\Business\RecurringPlanStatus;
use App\Enumerations\Business\SupportedCurrencyCode;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Exceptions\CollectionFailedException;
use App\Logics\ConfigurationRepository;
use Exception;
use HitPay\Business\Contracts\Chargeable as ChargeableContract;
use HitPay\Business\Contracts\Ownable as BusinessOwnableContract;
use HitPay\Business\HasCustomer;
use HitPay\Business\Ownable as BusinessOwnable;
use HitPay\DBS\Fast\GPC;
use HitPay\Model\UsesUuid;
use HitPay\Stripe\Charge as StripeCharge;
use HitPay\User\Contracts\Ownable as UserOwnableContract;
use HitPay\User\Ownable as UserOwnable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Log as LogFacade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Stripe\BalanceTransaction;
use Stripe\PaymentIntent as StripePaymentIntent;

class RecurringBilling extends Model implements BusinessOwnableContract, ChargeableContract, UserOwnableContract
{
    use BusinessOwnable, HasCustomer, Notifiable, UserOwnable, UsesUuid;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'business_subscribed_recurring_plans';

    protected $casts = [
        'data' => 'array',
        'payment_methods' => 'array',
        'trial_ends_at' => 'datetime',
        'expires_at' => 'datetime',
        'times_to_be_charged' => 'int',
        'times_charged' => 'int',
    ];

    public function isCompleted() : bool
    {
        return $this->status === RecurringPlanStatus::COMPLETED;
    }

    public function isValid() : bool
    {
        switch ($this->status) {
            case RecurringPlanStatus::CANCELED:
                return false;

            case RecurringPlanStatus::SCHEDULED:
                if ($this->expires_at->endOfDay()->isPast()) {
                    return false;
                }

                return true;

            case RecurringPlanStatus::ACTIVE:
                return true;
        }

        throw new Exception('Unknown status detected');
    }

    public function charge(int $cycle = 1) : Charge
    {
        if ($this->isCompleted()
            || ($this->times_to_be_charged !== null
                && $this->times_to_be_charged === $this->times_charged)) {
            throw new Exception('The recurring plan is completed.');
        } elseif (!$this->exists || $this->status !== RecurringPlanStatus::ACTIVE) {
            throw new Exception('You can\'t charge a non-exist or non-active plan.');
        }

        if ($this->payment_provider === PaymentProviderEnum::DBS_SINGAPORE) {
            if ($cycle !== 1) {
                throw new Exception('You can\'t charge more than 1 cycle via direct debit collection.');
            }

            $paymentProvider = $this->business->paymentProviders()
                ->where('payment_provider', PaymentProviderEnum::DBS_SINGAPORE)
                ->first();

            if (!$paymentProvider instanceof PaymentProvider) {
                throw new Exception('You have to setup your bank account before you can charge a plan via Collection.');
            }
        } elseif ($this->payment_provider === PaymentProviderEnum::STRIPE_SINGAPORE) {
            $paymentProvider = $this->business->paymentProviders()
                ->where('payment_provider', $this->business->payment_provider)
                ->first();

            if (!$paymentProvider instanceof PaymentProvider) {
                throw new Exception('You have to connect to Stripe account before you can charge a plan.');
            }
        } else {
            throw new Exception('Invalid payment provider set for recurring plan.');
        }

        $charge = new Charge;

        $charge->id = Str::orderedUuid()->toString();
        $charge->channel = Channel::RECURRENT;
        $charge->payment_provider = $paymentProvider->payment_provider;
        $charge->payment_provider_account_id = $paymentProvider->payment_provider_account_id;
        $charge->business_customer_id = $this->business_customer_id;
        $charge->customer_name = $this->customer_name;
        $charge->customer_email = $this->customer_email;
        $charge->customer_phone_number = $this->customer_phone_number;
        $charge->customer_street = $this->customer_street;
        $charge->customer_city = $this->customer_city;
        $charge->customer_state = $this->customer_state;
        $charge->customer_postal_code = $this->customer_postal_code;
        $charge->customer_country = $this->customer_country;
        $charge->currency = $this->currency;
        $charge->amount = bcmul($this->price, $cycle);

        $charge->target()->associate($this);

        $start = $this->expires_at->clone()->addDay();
        $until = $this->getNextChargeDate($cycle);

        $remark = 'Recurring Plan from '.$start->toDateString().' To '.$until->toDateString();

        $charge->remark = Str::limit($remark, 240);

        $filename = 'recurring-plan'.DIRECTORY_SEPARATOR.$charge->getKey().'.txt';

        if ($this->payment_provider === PaymentProviderEnum::DBS_SINGAPORE) {
            $data = $this->data['dbs']['dda'];

            $collection = GPC::new($charge->id, $this->dbs_dda_reference)
                ->setAmount(getReadableAmountByCurrency($this->currency, $this->price))
                ->setReceiverName($data['receiver_name'])
                ->setReceiverBankSwiftCode($data['receiving_bank_identifier_code'])
                ->setReceiverAccountNumber($data['receiving_bank_account_number'])
                ->setBusinessName($this->business->getName())
                ->setReceiverEmail($this->customer_email)
                ->process();

            $response = $collection->getResponseBody();

            Storage::append($filename, json_encode([
                'request' => $collection->getRequestBody(),
                'response' => $response,
            ], JSON_PRETTY_PRINT));

            if (!isset($response['txnResponse']['txnStatus']) || $response['txnResponse']['txnStatus'] !== 'ACTC') {
                throw new CollectionFailedException($response['txnRejectCode'], $response);
            }

            $charge->payment_provider_charge_type = PaymentMethodType::COLLECTION;
            $charge->payment_provider_charge_id = $response['txnResponse']['txnRefId'];
            $charge->payment_provider_charge_method = 'direct_debit';
            $charge->payment_provider_transfer_type = 'wallet';
            $charge->status = 'succeeded';
            $charge->data = $response;
            $charge->closed_at = $charge->freshTimestamp();

            [
                $fixedAmount,
                $percentage,
            ] = $paymentProvider->getRateFor(
                $this->business->country, $this->business->currency, $charge->currency, $charge->channel,
                $charge->payment_provider_charge_method, null, null, $charge->amount
            );

            $charge->home_currency = $this->currency;
            $charge->home_currency_amount = $this->price;
            $charge->exchange_rate = 1;
            $charge->fixed_fee = $fixedAmount;
            $charge->discount_fee_rate = $percentage;
            $charge->discount_fee = bcmul($charge->discount_fee_rate, $charge->home_currency_amount);

            $this->expires_at = $until;
            $this->failed_reason = null;

            if ($this->times_to_be_charged !== null) {
                $this->times_charged = $this->times_charged + $cycle;

                if ($this->times_to_be_charged === $this->times_charged) {
                    $this->status = RecurringPlanStatus::COMPLETED;
                }
            }

            try {
                DB::transaction(function () use ($charge) {
                    $this->save();
                    $this->business->charges()->save($charge);
                }, 3);

                return $charge;
            } catch (Exception $exception) {
                $message = "Error: ".get_class($exception)." - ".$exception->getMessage()."\n";
                $message .= $exception->getFile().":".$exception->getLine()."\n";
                $message .= json_encode($exception->getTrace(), JSON_PRETTY_PRINT)."\n";
                $message .= "Completing payment failed in our server, the processes should be already done in"
                    ." Collection. Please do reconciliation.\n";
                $message .= json_encode([
                    $this->toArray(),
                    $charge->toArray(),
                ], JSON_PRETTY_PRINT);

                LogFacade::critical($message);

                Storage::append($filename, "\n".$message);

                throw $exception;
            }
        }

        $stripeChargeHelper = StripeCharge::new($this->business->payment_provider);

        $stripePaymentIntent = StripePaymentIntent::create([
            'amount' => $charge->amount,
            'currency' => $charge->currency,
            'customer' => $this->payment_provider_customer_id,
            'payment_method' => $this->payment_provider_payment_method_id,
            // 'error_on_requires_action' => true,
            'confirm' => true,
            'off_session' => true,
            'metadata' => [
                'platform' => Config::get('app.name'),
                'version' => ConfigurationRepository::get('platform_version'),
                'environment' => Config::get('app.env'),
                'business_id' => $this->business->getKey(),
                'charge_id' => $charge->getKey(),
                'recurring_plan_id' => $this->getKey(),
            ],
        ]);

        /** @var \Stripe\Charge $stripeCharge */
        $stripeCharge = $stripePaymentIntent->charges->data[0];

        Storage::append($filename, $stripePaymentIntent->toJSON()."\n".$stripeCharge->toJSON());

        $charge->payment_provider_charge_type = $stripeCharge->object;
        $charge->payment_provider_charge_id = $stripeCharge->id;
        $charge->payment_provider_charge_method = $stripeCharge->payment_method_details->type;
        $charge->payment_provider_transfer_type = 'destination';
        $charge->status = $stripeCharge->status;
        $charge->data = $stripeCharge->toArray();

        $charge->closed_at = $charge->freshTimestamp();

        [
            $fixedAmount,
            $percentage,
        ] = $paymentProvider->getRateFor(
            $this->business->country, $this->business->currency, $charge->currency, $charge->channel,
            $charge->payment_provider_charge_method, $stripeCharge->payment_method_details->card->country,
            $stripeCharge->payment_method_details->card->brand, $charge->amount
        );

        $balanceTransaction = BalanceTransaction::retrieve($stripeCharge->balance_transaction);

        $charge->home_currency = $balanceTransaction->currency;
        $charge->home_currency_amount = $balanceTransaction->amount;
        $charge->exchange_rate = $balanceTransaction->exchange_rate;
        $charge->fixed_fee = $fixedAmount;
        $charge->discount_fee_rate = $percentage;
        $charge->discount_fee = bcmul($charge->discount_fee_rate, $charge->home_currency_amount);

        $stripeTransfer = $stripeChargeHelper->transfer($stripeCharge->id, $charge->payment_provider_account_id,
            $balanceTransaction->currency, bcsub($charge->home_currency_amount, $charge->getTotalFee()), $charge);

        Storage::append($filename, "\n".$stripeTransfer->toJSON());

        $transfer = new Transfer;
        $transfer->business_id = $this->business->getKey();
        $transfer->payment_provider = $charge->payment_provider;
        $transfer->payment_provider_account_id = $charge->payment_provider_account_id;
        $transfer->payment_provider_transfer_type = $stripeTransfer->object;
        $transfer->payment_provider_transfer_id = $stripeTransfer->id;
        $transfer->payment_provider_transfer_method = 'destination';
        $transfer->currency = $stripeTransfer->currency;
        $transfer->amount = $stripeTransfer->amount;
        $transfer->status = 'succeeded';
        $transfer->data = $stripeTransfer->toArray();

        $this->expires_at = $until;
        $this->failed_reason = null;

        if ($this->times_to_be_charged !== null) {
            $this->times_charged = $this->times_charged + $cycle;

            if ($this->times_to_be_charged === $this->times_charged) {
                $this->status = RecurringPlanStatus::COMPLETED;
            }
        }

        try {
            DB::transaction(function () use ($charge, $transfer) {
                $this->save();
                $this->business->charges()->save($charge);
                $charge->transfers()->save($transfer);
            }, 3);

            return $charge;
        } catch (Exception $exception) {
            $message = "Error: ".get_class($exception)." - ".$exception->getMessage()."\n";
            $message .= $exception->getFile().":".$exception->getLine()."\n";
            $message .= json_encode($exception->getTrace(), JSON_PRETTY_PRINT)."\n";
            $message .= "Completing payment failed in our server, the processes should be already done in Stripe."
                ." Please do reconciliation.\n";
            $message .= json_encode([
                $this->toArray(),
                $charge->toArray(),
                $transfer->toArray(),
            ], JSON_PRETTY_PRINT);

            LogFacade::critical($message);

            Storage::append($filename, "\n".$message);

            throw $exception;
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany|\App\Business\Charge|\App\Business\Charge[]
     */
    public function charges() : MorphMany
    {
        return $this->morphMany(Charge::class, 'charges', 'business_target_type', 'business_target_id', 'id');
    }

    public function extendExpiryDate(int $cycleCount = 1)
    {
        $time = $this->getNextChargeDate($cycleCount);
        $query = $this->setKeysForSaveQuery($this->newModelQuery());
        $columns = [
            'expires_at' => $this->fromDateTime($time),
            'updated_at' => $this->fromDateTime($time),
        ];

        $this->expires_at = $time;
        $this->updated_at = $time;

        $query->update($columns);
        $this->syncOriginalAttributes(array_keys($columns));
    }

    public function getNextChargeDate(int $cycleCount = 1)
    {
        switch ($this->cycle) {
            case RecurringCycle::WEEKLY:
                return $this->expires_at->addWeeks($cycleCount);

                break;

            case RecurringCycle::MONTHLY:
                return $this->expires_at->addMonths($cycleCount);

                break;

            case RecurringCycle::YEARLY:
                return $this->expires_at->addYears($cycleCount);

                break;

            default:
                throw new Exception('Unknown cycle detected');
        }
    }

    public function getPrice()
    {
        return getFormattedAmount($this->currency, $this->price);
    }

    public function routeNotificationForMail()
    {
        return $this->customer_email ?? $this->customer->email ?? null;
    }

    /**
     * Get the customer of the subscribed recurring plan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business\Customer
     */
    public function customer() : BelongsTo
    {
        return $this->belongsTo(Customer::class, 'business_customer_id', 'id', 'customer');
    }

    public function getAmount() : int
    {
        return $this->getAttribute('amount');
    }

    public function getChannel() : string
    {
        return $this->getAttribute('channel');
    }

    public function getCurrency() : string
    {
        return $this->getAttribute('currency');
    }
}
