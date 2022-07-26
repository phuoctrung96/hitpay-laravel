<?php

namespace App\Business;

use App\Actions\UseLogViaStorage;
use App\Business;
use App\Enumerations;
use App\Enumerations\Business\Event;
use App\Enumerations\BusinessRole;
use App\Enumerations\CurrencyCode;
use App\Enumerations\PaymentProvider;
use App\Exceptions\HitPayLogicException;
use App\Logics\ConfigurationRepository;
use App\Models\Business\SpecialPrivilege;
use App\Notifications\Business\NotifySuccessfulPaynowPayout;
use App\Notifications\Business\Stripe\NotifySuccessfulWalletPayout;
use App\Role;
use ErrorException;
use GuzzleHttp\Exception\ClientException;
use HitPay\Agent\LogHelpers;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Data\ExchangeRate;
use HitPay\Model\UsesUuid;
use HitPay\PayNow\DecryptException;
use HitPay\PayNow\EncryptException;
use HitPay\PayNow\Fast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use League\Csv\Writer;
use Throwable;

class Transfer extends Model implements OwnableContract
{
    use LogHelpers, Ownable, UsesUuid, UseLogViaStorage;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'business_transfers';

    protected $casts = [
        'counter' => 'int',
        'data' => 'array',
        'transferred_at' => 'datetime',
    ];

    public static $availableBankSwiftCodes = [
        'ANZBSGSXXXX' => 'Australia & New Zealand Banking Group',
        'BNPASGSGXXX' => 'BNP Paribas',
        'BKCHSGSGXXX' => 'Bank Of China Limited',
        'BOTKSGSXXXX' => 'The Bank Of Tokyo-Mitsubishi UFJ, Ltd',
        'CIBBSGSGXXX' => 'CIMB Bank Berhad',
        'CITISGSGXXX' => 'Citibank NA',
        'CITISGSLXXX' => 'Citibank Singapore Limited',
        'DEUTSGSGXXX' => 'Deutsche Bank AG',
        'DBSSSGSGXXX' => 'DBS Bank',
        'HLBBSGSGXXX' => 'HL Bank',
        'HSBCSGSGXXX' => 'HSBC (Corporate)',
        'HSBCSGS2XXX' => 'HSBC (Personal)',
        'ICICSGSGXXX' => 'ICICI Bank Limited',
        'ICBKSGSGXXX' => 'Industrial and Commercial Bank Of China Limited',
        'MBBESGS2XXX' => 'Malayan Singapore Limited',
        'MHCBSGSGXXX' => 'Mizuho Bank Limited',
        'OCBCSGSGXXX' => 'Oversea-Chinese Banking Corpn Ltd',
        'RHBBSGSGXXX' => 'RHB Bank Berhad',
        'SCBLSG22XXX' => 'Standard Chartered Bank (Singapore) Limited',
        'SMBCSGSGXXX' => 'Sumitomo Mitsui Banking Corporation',
        'UOVBSGSGXXX' => 'United Overseas Bank Ltd',
        'SIVFSGSG' => 'Sing Investments & Finance Limited',
    ];

    public function business() : BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id', 'id', 'charge');
    }

    /**
     * Get the charge of this transfer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business\Charge
     */
    public function charge() : BelongsTo
    {
        return $this->belongsTo(Charge::class, 'business_charge_id', 'id', 'charge');
    }

    /**
     * Get the charges of this transfer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\App\Business\Charge|\App\Business\Charge[]
     */
    public function charges() : BelongsToMany
    {
        return $this->belongsToMany(Charge::class, 'business_charge_transfer', 'transfer_id', 'charge_id', 'id', 'id',
            'charges');
    }

    public function transactions() : BelongsToMany
    {
        return $this->belongsToMany(
            Business\Wallet\Transaction::class,
            'business_transaction_transfer',
            'transfer_id',
            'transaction_id',
            'id',
            'id',
            __FUNCTION__
        );
    }

    public function walletTransactions() : MorphMany
    {
        return $this->morphMany(Wallet\Transaction::class, 'relatable', null, null, 'id');
    }

    /**
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Crypt_GPG_BadPassphraseException
     * @throws \Crypt_GPG_Exception
     * @throws \Crypt_GPG_FileException
     * @throws \Crypt_GPG_KeyNotFoundException
     * @throws \Crypt_GPG_NoDataException
     * @throws \PEAR_Exception
     * @throws \ReflectionException
     */
    public function doFastTransfer()
    {
        switch (false) {
            case $this->exists:
                throw new HitPayLogicException('The transfer is not exist');
            case $this->payment_provider === PaymentProvider::DBS_SINGAPORE:
                throw new HitPayLogicException('The transfer is not a DBS transfer');
            case $this->status === 'request_pending';
                throw new HitPayLogicException('The transfer status is not `request_pending`');
            case $this->amount !== 0:
                throw new HitPayLogicException('The transfer has zero amount!');
        }

        $business = $this->business;

        try {
            [
                $bankSwiftCode,
                $bankAccountNumber,
            ] = explode('@', $this->payment_provider_account_id);

            if (!isset(static::$availableBankSwiftCodes[$bankSwiftCode])) {
                $message = 'The payment provider DBS for business [ID: '.$business->id.'] has an invalid swift code'
                    .' [Value: '.$bankSwiftCode.']. A transfer is still created for this account with status pending,'
                    .' admin can update the account for this transfer and trigger it again.';
            } elseif (!preg_match('/^[0-9]+$/', $bankAccountNumber)) {
                $message = 'The payment provider DBS for business [ID: '.$business->id.'] has an invalid bank transfer'
                    .' is account number, it is having non-digits value [Value: '.$bankAccountNumber.']. However, a'
                    .' still created for this account with status pending, admin can update the account for this'
                    .' transfer and trigger it again.';
            }
        } catch (ErrorException $exception) {
            $message = 'The payment provider DBS for business [ID: '.$business->id.'] has an invalid bank account'
                .' format. It should be ":swift_code@:bank_account_number", but there\'s error exception.';
        }

        if (isset($message)) {
            Log::channel('available-balance-payouts')->critical($message);

            throw new HitPayLogicException($message);
        }

        $data = $this->data;

        if ($this->payment_provider_transfer_method === 'wallet_fast') {
            $charges = 'View the payout details via HitPay Dashboard';
        } else {
            $charges = $this->charges;
        }

        $cacheKey = "trying_transfer_{$this->getKey()}";

        if (Cache::get($cacheKey)) {
            Log::channel('available-balance-payouts')
                ->info("The transfer '{$this->getKey()}' might having an in progress payment, but a new request received.");

            return;
        }

        Cache::set($cacheKey, true, Date::now()->addMinutes(15));

        $fast = Fast::transfer($this->getKey(), $this->counter);

        try {
            $fastData = $fast
                ->setAmount(getReadableAmountByCurrency($this->currency, $this->amount))
                ->setBusinessName($business->getName())
                ->setReceiverBankSwiftCode($bankSwiftCode)
                ->setReceiverEmail($business->routeNotificationForMail())
                ->setReceiverName($data['company']['name'] ?? $business->getName())
                ->setReceiverAccountNumber($bankAccountNumber)
                ->setTransactionDetails($charges)
                ->generate();
        } catch (EncryptException|DecryptException|ClientException $exception) {
            if ($exception instanceof EncryptException) {
                $message = 'Encryption failed, since no API is called, it\'s safe to re-trigger.';
            } elseif ($exception instanceof DecryptException) {
                $message = 'Decryption failed, have to check the log file.';
            } elseif ($exception instanceof ClientException) {
                $message = 'API returned exception? The API is actually called, better to check the log file.';
            }

            $exception = [
                'timestamp' => Date::now()->toDateTimeString('microsecond'),
                'message' => $message ?? 'If you see this, we have to check the code.',
                'filename' => $fast->getFilename(),
                'exception' => [
                    'class' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'location' => "{$exception->getFile()}:{$exception->getLine()}",
                ],
            ];
        } catch (Throwable $throwable) {
            Cache::forget($cacheKey);

            throw $throwable;
        }

        if (isset($exception)) {
            $data['activities'][] = $exception;
        } elseif (isset($fastData)) {
            if ($fastData->getErrorMessages()) {
                Log::channel('available-balance-payouts')->critical(implode("\n", $fastData->getErrorMessages()));
            }

            $fastDataResponse = $fastData->getResponse();

            // Not sure if the success response change, they only mentioned response for RJCT.
            if (isset($fastDataResponse['txnResponse']['txnStatus'])
                && $fastDataResponse['txnResponse']['txnStatus'] === 'ACTC') {
                $this->payment_provider_transfer_id = $fastDataResponse['txnResponse']['txnRefId'];
                $this->payment_provider_transfer_type = 'fast';
                $this->status = 'succeeded';
                $this->transferred_at = $this->freshTimestamp();
            } elseif (!is_int($this->counter)) {
                $this->counter = 0;
            } else {
                $this->counter++;
            }

            $data['requests'][] = [
                'body' => $fastData->getRequestBody(),
                'response' => $fastDataResponse,
            ];
        }

        $this->data = $data;

        $this->save();

        Cache::forget($cacheKey);

        if ($this->status === 'succeeded') {
            try {
                $notification = new NotifySuccessfulPaynowPayout($this);

                if ($this->business->subscribedEvents()->where('event', Event::DAILY_PAYOUT)->first() != null)
                    $this->business->notify($notification);

                $role = Role::where('title', BusinessRole::ADMIN)->first();

                if ($role) {
                    $businessUsers = $this->business->businessUsers()->with('user')->where('role_id', $role->id)->get();

                    foreach ($businessUsers as $user) {
                        Notification::route('mail', $user->user->email)->notify($notification);
                    }
                }
            } catch (\Exception $exception) {
                Log::channel('available-balance-payouts')
                    ->critical("'PayNow Payout email for business ID [{$this->business_id}], date [{$this->created_at->toDateString()}] sending failed.'");
            }
        }
    }

    /**
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Crypt_GPG_BadPassphraseException
     * @throws \Crypt_GPG_Exception
     * @throws \Crypt_GPG_FileException
     * @throws \Crypt_GPG_KeyNotFoundException
     * @throws \Crypt_GPG_NoDataException
     * @throws \PEAR_Exception
     * @throws \ReflectionException
     */
    public function doStripeConnectTransfer()
    {
        switch (false) {
            case $this->exists:
                throw new HitPayLogicException('The transfer is not exist');
            case in_array($this->payment_provider, [PaymentProvider::STRIPE_MALAYSIA, PaymentProvider::STRIPE_US]):
                throw new HitPayLogicException('The transfer is not a Stripe (Malaysia/US) transfer');
            case $this->status === 'request_pending';
                throw new HitPayLogicException('The transfer status is not `request_pending`');
            case $this->amount !== 0:
                throw new HitPayLogicException('The transfer has zero amount!');
        }

        $business = $this->business;

        $stripe_account_no = $this->payment_provider_account_id;

        $data = $this->data;

        $cacheKey = "trying_transfer_{$this->getKey()}";

        if (Cache::get($cacheKey)) {
            Log::channel('available-balance-payouts')
                ->info("The transfer '{$this->getKey()}' might having an in progress payment, but a new request received.");

            return;
        }

        Cache::set($cacheKey, true, Date::now()->addMinutes(15));

        try {
            if ($this->payment_provider === PaymentProvider::STRIPE_US) {
                $currency = CurrencyCode::USD;

                if ($this->currency !== $currency) {
                    $commissionRate = (float)ConfigurationRepository::get('stripe_us_fx_fee', 0.02);
                    $exchangeRate = ExchangeRate::new($this->currency, $currency)->get();

                    $convertedAmount = (int)bcmul(
                        $exchangeRate,
                        $this->amount,
                    );

                    $commissionAmount = (int)bcmul(
                        $commissionRate,
                        $convertedAmount,
                    );

                    $amount = $convertedAmount-$commissionAmount;
                } else {
                    $amount = $this->amount;
                }
            } else {
                $amount = $this->amount;
                $currency = $this->currency;
            }

            $transferBody = [
                'amount' => $amount,
                'currency' => $currency,
                'destination' => $stripe_account_no,
                'description' => $this->remark,
                'metadata' => [
                    'platform' => \Config::get('app.name'),
                    'version' => ConfigurationRepository::get('platform_version'),
                    'environment' => \Config::get('app.env'),
                    'business_id' => $business->id,
                    'transfer_id' => $this->id
                ],
            ];

            if ($this->payment_provider === PaymentProvider::STRIPE_MALAYSIA) {
                $stripe = new \Stripe\StripeClient(config('services.stripe.my.secret'));
            } elseif ($this->payment_provider === PaymentProvider::STRIPE_US) {
                $stripe = new \Stripe\StripeClient(config('services.stripe.us.secret'));
            }

            $transfer = $stripe->transfers->create($transferBody);
        } catch (\Stripe\Exception\ApiErrorException $exception) {
            Log::channel('available-balance-payouts')->critical((string)$exception);

            $message = 'API returned exception? The API is actually called, better to check the log file.';

            $exception = [
                'timestamp' => Date::now()->toDateTimeString('microsecond'),
                'message' => $message ?? 'If you see this, we have to check the code.',
                'exception' => [
                    'class' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'location' => "{$exception->getFile()}:{$exception->getLine()}",
                ],
            ];
        } catch (Throwable $throwable) {
            Cache::forget($cacheKey);

            throw $throwable;
        }

        if (isset($exception)) {
            $data['activities'][] = $exception;
        } else {
            $this->payment_provider_transfer_id = $transfer->id;
            $this->payment_provider_transfer_type = $this->payment_provider;
            $this->status = 'succeeded';
            $this->transferred_at = $this->freshTimestamp();

            $data['requests'][] = [
                'body' => $transferBody,
                'response' => $transfer->getLastResponse(),
            ];
        }

        $this->data = $data;

        $this->save();

        Cache::forget($cacheKey);

        $this->now = \Illuminate\Support\Facades\Date::now();
        $this->setLogDirectories('payment_providers', $this->payment_provider, 'connect-wallet-payouts');
        $this->setLogFilename("{$business->getKey()}-{$transfer->id}.txt");
        $this->log($transfer->toJSON());

        if ($this->status === 'succeeded') {
            try {
                $notification = new NotifySuccessfulWalletPayout($this);

                if ($this->business->subscribedEvents()->where('event', Event::DAILY_PAYOUT)->first() != null)
                    $this->business->notify($notification);

                $role = Role::where('title', BusinessRole::ADMIN)->first();

                if ($role) {
                    $businessUsers = $this->business->businessUsers()->with('user')->where('role_id', $role->id)->get();

                    foreach ($businessUsers as $user) {
                        Notification::route('mail', $user->user->email)->notify($notification);
                    }
                }
            } catch (\Exception $exception) {
                Log::channel('available-balance-payouts')
                    ->critical("'Stripe Payout email for business ID [{$this->business_id}], date [{$this->created_at->toDateString()}] sending failed.'");
            }
        }
    }

    public function generateCsv()
    {
        $includeChargeId = $this->business
            ->specialPrivileges()
            ->where('special_privilege', SpecialPrivilege::TRANSFER_CSV_WITH_CHARGE_ID)
            ->exists();

        $this->load([
            'transactions' => function (BelongsToMany $query) use ($includeChargeId) {
                if ($includeChargeId) {
                    $query->with('relatable');
                }

                return $query;
            },
        ]);

        $transactions = $this->transactions->merge($this->walletTransactions);

        $csv = Writer::createFromString();

        $header = [
            '#',
            'Datetime',
            'Description',
        ];

        if ($includeChargeId) {
            $header[] = 'Charge ID';
        }

        $header = array_merge($header, [
            'Debit',
            'Credit',
            'Balance',
        ]);

        $csv->insertOne($header);

        $i = 1;

        $data = [];

        /* @var \App\Business\Wallet\Transaction $transaction */
        foreach ($transactions as $transaction) {
            $content = [
                '#' => $i++,
                'Datetime' => $transaction->created_at->toDateTimeString(),
                'Description' => $transaction->description,
            ];

            if ($includeChargeId) {
                switch (false) {
                    case $transaction->event === Enumerations\Business\Wallet\Event::CONFIRMED_CHARGE:
                    case $transaction->relatable instanceof Business\Wallet\Transaction:
                    case $transaction->relatable->event === Enumerations\Business\Wallet\Event::RECEIVED_FROM_CHARGE:
                    case $transaction->relatable->relatable_type === 'business_charge':

                        $content['Charge Id'] = '-';
                        break;

                    default:
                        $content['Charge Id'] = $transaction->relatable->relatable_id;
                }
            }

            $content = array_merge($content, [
                'Debit' => $transaction->amount < 0
                    ? getReadableAmountByCurrency($this->currency, $transaction->amount) : '-',
                'Credit' => $transaction->amount > 0
                    ? getReadableAmountByCurrency($this->currency, $transaction->amount) : '-',
                'Balance' => getReadableAmountByCurrency($this->currency, $transaction->balance_after),
            ]);

            $data[] = $content;
        }

        $csv->insertAll($data);

        return (string) $csv;
    }
}
