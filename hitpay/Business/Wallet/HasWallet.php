<?php

namespace HitPay\Business\Wallet;

use App\Business\Charge;
use App\Business\PaymentProvider;
use App\Business\Refund;
use App\Business\Transfer;
use App\Business\Wallet;
use App\Enumerations;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\Business\Wallet as WalletEnums;
use App\Exceptions\InsufficientFund;
use App\Exceptions\WalletException;
use App\Jobs;
use App\Jobs\SubmitChargeForMonitoring;
use App\Models\Business\BankAccount;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades;
use Illuminate\Support\Str;
use Throwable;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasWallet
{
    /**
     * The wallets belongs to this model.
     *
     * @param string|null $type
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function wallets(string $type = null) : HasMany
    {
        $wallets = $this->hasMany(Wallet::class, 'business_id', 'id');

        if (!is_null($type)) {
            $wallets->where('type', $type);
        }

        return $wallets;
    }

    /**
     * The pending wallets belongs to this model.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function pendingBalances() : Collection
    {
        return $this->wallets(WalletEnums\Type::PENDING)->get();
    }

    /**
     * The available wallets belongs to this model.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function availableBalances() : Collection
    {
        return $this->wallets(WalletEnums\Type::AVAILABLE)->get();
    }

    /**
     * The reserve wallets belongs to this model.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function reserveBalances() : Collection
    {
        return $this->wallets(WalletEnums\Type::RESERVE)->get();
    }

    /**
     * The deposit wallets belongs to this model.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function depositBalances() : Collection
    {
        return $this->wallets(WalletEnums\Type::DEPOSIT)->get();
    }

    /**
     * The get wallet helper.
     *
     * @param string $type
     * @param string $currency
     * @param bool $createIfNotExists
     *
     * @return \App\Business\Wallet|null
     */
    public function wallet(string $type, string $currency, bool $createIfNotExists = true) : ?Wallet
    {
        $attributes = compact('type', 'currency');

        $wallet = $this->wallets()->where($attributes)->first();

        if (!$wallet instanceof Wallet && $createIfNotExists) {
            $wallet = new Wallet(array_merge($attributes, [
                'balance' => 0,
                'reserve_balance' => 0,
            ]));

            $this->wallets()->save($wallet);
        }

        return $wallet;
    }

    /**
     * The pending balance wallet for selected currency.
     *
     * @param string $currency
     *
     * @return \App\Business\Wallet
     */
    public function pendingBalance(string $currency) : Wallet
    {
        return $this->wallet(WalletEnums\Type::PENDING, $currency);
    }

    /**
     * The available balance wallet for selected currency.
     *
     * @param string $currency
     *
     * @return \App\Business\Wallet
     */
    public function availableBalance(string $currency) : Wallet
    {
        return $this->wallet(WalletEnums\Type::AVAILABLE, $currency);
    }

    /**
     * The reserve balance wallet for selected currency.
     *
     * @param string $currency
     *
     * @return \App\Business\Wallet
     */
    public function reserveBalance(string $currency) : Wallet
    {
        return $this->wallet(WalletEnums\Type::RESERVE, $currency);
    }

    /**
     * The deposit balance wallet for selected currency.
     *
     * @param string $currency
     *
     * @return \App\Business\Wallet
     */
    public function depositBalance(string $currency) : Wallet
    {
        return $this->wallet(WalletEnums\Type::DEPOSIT, $currency);
    }

    /**
     * The wallet transactions belongs to this model.
     *
     * @param string|null $event
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function walletTransactions(string $event = null) : HasMany
    {
        $wallets = $this->hasMany(Wallet\Transaction::class, 'business_id', 'id');

        if (!is_null($event)) {
            $wallets->where('event', $event);
        }

        return $wallets;
    }

    /**
     * Receive fund from charge.
     *
     * @param \App\Business\Charge $charge
     * @param bool $confirmed
     *
     * @return \App\Business\Wallet\Transaction[]
     * @throws \App\Exceptions\WalletException
     * @throws \Throwable
     */
    public function receiveFromCharge(Charge $charge, bool $confirmed = false) : array
    {
        if (!$charge->exists) {
            throw new WalletException(sprintf('Charge ID: %s does not exist.', $charge->getKey()));
        } elseif ($this->getKey() !== $charge->business_id) {
            throw new WalletException(sprintf('The charge ID: %s is not belongs to this business ID: %s.',
                $charge->getKey(), $this->getKey()));
        } elseif ($charge->status !== ChargeStatus::SUCCEEDED) {
            throw new WalletException(sprintf('Unsupported charge ID: %s with status \'%s\' to receive the fund. The status must be \'succeeded\'.',
                $charge->getKey(), $charge->status));
        } elseif ($charge->payment_provider_transfer_type !== 'wallet') {
            throw new WalletException(sprintf('Unsupported charge ID: %s with transfer type \'%s\' for wallet to receive the fund. The transfer type must be \'wallet\'.',
                $charge->getKey(), $charge->payment_provider_transfer_type));
        } elseif (!$this->isValidCharge($charge, false)) {
            throw new WalletException(sprintf('Unsupported charge ID: %s via payment provider \'%s\' for wallet to receive the fund.',
                $charge->getKey(), $charge->payment_provider));
        }

        $transaction = $charge->walletTransactions()->where('event', WalletEnums\Event::RECEIVED_FROM_CHARGE)->first();

        if ($transaction instanceof Wallet\Transaction) {
            throw new WalletException(sprintf('Charge ID: %s is already processed, please check transaction ID: %s.',
                $charge->getKey(), $transaction->getKey()));
        }

        Facades\DB::beginTransaction();

        try {
            $transaction = $this->pendingBalance($charge->currency)->incoming(
                WalletEnums\Event::RECEIVED_FROM_CHARGE,
                (int) bcsub($charge->amount, $charge->getTotalFee()),
                'Received payment for Charge # '.$charge->getKey(),
                [
                    'charge' => [
                        'id' => $charge->getKey(),
                        'currency' => $charge->currency,
                        'amount' => $charge->amount,
                        'amount_text' => getReadableAmountByCurrency($charge->currency, $charge->amount),
                        'fee' => $charge->getTotalFee(),
                        'fee_text' => getReadableAmountByCurrency($charge->currency, $charge->getTotalFee()),
                    ],
                ],
                $charge,
                null,
                null,
                false
            );

            Facades\DB::commit();
        } catch (Throwable $exception) {
            Facades\DB::rollBack();

            throw $exception;
        }

        $transactions = [$transaction];

        if ($confirmed) {
            return array_merge($transactions, $this->confirmCharge($charge, $transaction));
        }

        return $transactions;
    }

    /**
     * Confirm the charge and move the fund to available.
     *
     * @param \App\Business\Charge $charge
     * @param \App\Business\Wallet\Transaction|null $transaction
     *
     * @return \App\Business\Wallet\Transaction[]
     * @throws \App\Exceptions\WalletException
     * @throws \Throwable
     */
    public function confirmCharge(Charge $charge, Wallet\Transaction $transaction = null) : array
    {
        if (is_null($transaction)) {
            if (!$charge->exists) {
                throw new WalletException(sprintf('Charge ID: %s does not exist.', $charge->getKey()));
            } elseif ($this->getKey() !== $charge->business_id) {
                throw new WalletException(sprintf('The charge ID: %s is not belongs to this business ID: %s.',
                    $charge->getKey(), $this->getKey()));
            }

            $transaction = $charge->walletTransactions()
                ->where('event', WalletEnums\Event::RECEIVED_FROM_CHARGE)->first();

            if (!($transaction instanceof Wallet\Transaction)) {
                throw new WalletException(sprintf('No transaction detected for charge ID: %s, wallet cannot confirm.',
                    $charge->getKey()));
            } elseif ($this->getKey() !== $transaction->business_id) {
                throw new WalletException(sprintf('The transaction ID: %s is not belongs to this business ID: %s, please check charge ID: %s too.',
                    $transaction->getKey(), $this->getKey(), $charge->getKey()));
            }

            $wallet = $transaction->wallet;

            if ($this->getKey() !== $wallet->business_id) {
                throw new WalletException(sprintf('The wallet ID: %s is not belongs to this business ID: %s.',
                    $wallet->getKey(), $charge->getKey()));
            } elseif ($wallet->type !== WalletEnums\Type::PENDING) {
                throw new WalletException(sprintf('The transaction ID: %s is not in pending wallet.',
                    $transaction->getKey()));
            } elseif ($wallet->currency !== $charge->currency) {
                throw new WalletException(sprintf('The charge ID: %s currency is different with pending wallet ID: %s.',
                    $charge->getKey(), $wallet->getKey()));
            } elseif ($transaction->confirmed) {
                throw new WalletException(sprintf('The charge ID: %s is already confirmed, please check transaction ID: %s.',
                    $charge->getKey(), $transaction->getKey()));
            }
        }

        $pendingWallet = $wallet ?? $transaction->wallet;
        $currency = $pendingWallet->currency;
        $transactionAmountAvailable = $transaction->amount;
        $meta = $transaction->meta;

        Facades\DB::beginTransaction();

        try {
            $availableWallet = $this->availableBalance($currency);

            $outgoingDescription = 'Transferred to \''.strtoupper($currency.' @ '.$availableWallet->type).'\' '.
                'for confirmed charge # '.$charge->getKey();

            $transaction->addTimeline($outgoingDescription);

            $transactions[] = $pendingWallet->transfer(
                $availableWallet,
                $transactionAmountAvailable,
                null,
                array_merge($meta, [

                ]),
                $transaction,
                [
                    'outgoing_description' => $outgoingDescription,
                    'incoming_event' => WalletEnums\Event::CONFIRMED_CHARGE,
                    'incoming_description' => 'Received from \''.strtoupper($currency.' @ '.$pendingWallet->type).
                        '\' for confirmed charge # '.$charge->getKey(),
                ]
            );

            $transaction->confirmed = true;

            $transaction->save();

            Facades\DB::commit();
        } catch (Throwable $exception) {
            Facades\DB::rollBack();

            throw $exception;
        }

        Facades\DB::beginTransaction();

        try {
            $depositWallet = $this->depositBalance($currency);

            $amountToFillTargetedWallet = (int) bcsub($depositWallet->reserve_balance, $depositWallet->balance);

            if ($amountToFillTargetedWallet > 0) {
                $availableWallet = $this->availableBalance($currency);

                if ($amountToFillTargetedWallet >= $availableWallet->balance) {
                    $amountToTargetedWallet = $availableWallet->balance;
                } else {
                    $amountToTargetedWallet = $amountToFillTargetedWallet;
                }

                $availableWallet->transfer($depositWallet, $amountToTargetedWallet);
            }

            Facades\DB::commit();
        } catch (Throwable $exception) {
            Facades\DB::rollBack();

            throw $exception;
        }

        return $transactions ?? [];
    }

    public function invalidateCharge(Charge $charge)
    {
        // TODO - IMPORTANT
        //   -
        //   Withdraw the invalid charge from pending balance.
    }

    /**
     * Payout to bank.
     *
     * This method should be call from cron job.
     *
     * @param  \App\Business\PaymentProvider  $paymentProvider
     * @param  string  $currency
     * @param  \App\Models\Business\BankAccount  $bankAccount
     * @param  int|null  $amount
     *
     * @return \App\Business\Transfer
     * @throws \App\Exceptions\WalletException
     * @throws \Throwable
     */
    public function payoutToBank(
        PaymentProvider $paymentProvider, string $currency, BankAccount $bankAccount, int $amount = null
    ) : Transfer {
        $amount = null; // Temporary disabled amount to be passed in and withdraw all amount. Just in case.

        if (!$paymentProvider->exists) {
            throw new WalletException('The payment provider is not exist.');
        } elseif ($this->getKey() !== $paymentProvider->business_id) {
            throw new WalletException(sprintf('The payment provider ID: %s is not belongs to this business ID: %s.',
                $paymentProvider->getKey(), $this->getKey()));
        } elseif (!$bankAccount->exists) {
            throw new WalletException("The bank account ID: {$bankAccount->getKey()} is not exist for this business ID: {$this->getKey()}.");
        } elseif ($this->getKey() !== $bankAccount->business_id) {
            throw new WalletException("The bank account ID: {$bankAccount->getKey()} is not belongs to this business ID: {$this->getKey()}.");
        } elseif ($bankAccount->country !== $this->country) {
            throw new WalletException("The bank account ID: {$bankAccount->getKey()} must be in the same country for business ID: {$this->getKey()}.");
        } elseif ($bankAccount->currency !== $currency) {
            throw new WalletException("The bank account ID: {$bankAccount->getKey()} must be in currency '{$currency}' for this payout.");
        }

        if ($paymentProvider->payment_provider === Enumerations\PaymentProvider::DBS_SINGAPORE) {
            $bankSwiftCode = $bankAccount->bank_swift_code;
            $bankAccountNo = $bankAccount->number;

            $bankName = Transfer::$availableBankSwiftCodes[$bankSwiftCode] ?? ('Bank Swift Code: '.$bankSwiftCode);

            $outgoingDescription = 'Paid out to '.$bankName.' (Account No: '.$bankAccountNo.')';

            $meta = [
                'bank_name' => $bankName,
                'bank_swift_code' => $bankSwiftCode,
                'bank_account_no' => $bankAccountNo,
            ];
        } else {
            throw new WalletException(sprintf('The payment provider \'%s\' is currently not supported.',
                $paymentProvider->payment_provider));
        }

        $availableWallet = $this->availableBalance($currency);

        if ($availableWallet->balance <= 0) {
            throw new WalletException('The wallet has insufficient balance.');
        } elseif (is_null($amount)) {
            $amount = $availableWallet->balance;
        } elseif ($availableWallet->balance - $amount < 0) {
            throw new WalletException('Insufficient balance for payout.');
        }

        $lastPayout = $availableWallet->transactions()
            ->where('event', WalletEnums\Event::PAID_TO_BANK)
            ->orderByDesc('created_at')
            ->first();

        if ($lastPayout instanceof Wallet\Transaction) {
            $transactionsToBeAttached = $availableWallet->transactions()
                ->where('created_at', '>=', $lastPayout->created_at)
                ->get();
        } else {
            $transactionsToBeAttached = $availableWallet->transactions()->get();
        }

        $transfer = new Transfer;

        $transfer->payment_provider_transfer_method = 'wallet_fast';
        $transfer->payment_provider = Enumerations\PaymentProvider::DBS_SINGAPORE;
        $transfer->payment_provider_account_id = "{$bankAccount->bank_swift_code}@{$bankAccount->number}";
        $transfer->currency = $currency;
        $transfer->amount = $amount;
        $transfer->remark = 'HitPay balance payouts for '.Facades\Date::now()->toDateString();

        $transfer->data = [
            'bank_account' => $bankAccount->only([
                'id',
                'country',
                'currency',
                'bank_swift_code',
                'bank_routing_number',
                'number',
                'holder_name',
                'holder_type',
                'stripe_platform',
                'stripe_external_account_id',
            ]),
            'payment_provider' => $paymentProvider->data,
        ];

        $transfer->status = 'request_pending';

        Facades\DB::beginTransaction();

        try {
            $this->transfers()->save($transfer);

            if ($transactionsToBeAttached->count()) {
                $transfer->transactions()->attach($transactionsToBeAttached->pluck('id'));
            }

            $availableWallet->outgoing(
                WalletEnums\Event::PAID_TO_BANK,
                $amount,
                $outgoingDescription,
                $meta ?? [],
                $transfer,
                null,
                null,
                false
            );

            Facades\DB::commit();
        } catch (Throwable $exception) {
            Facades\DB::rollBack();

            throw $exception;
        }

        return $transfer;
    }

    /**
     * Payout to Stripe Wallet.
     *
     * This method should be call from cron job.
     *
     * @param  \App\Business\PaymentProvider  $paymentProvider
     * @param  string  $currency
     * @param  int|null  $amount
     *
     * @return \App\Business\Transfer
     * @throws \App\Exceptions\WalletException
     * @throws \Throwable
     */
    public function payoutToStripe(
        PaymentProvider $paymentProvider, string $currency, int $amount = null
    ) : Transfer {
        $amount = null; // Temporary disabled amount to be passed in and withdraw all amount. Just in case.

        if (!$paymentProvider->exists) {
            throw new WalletException('The payment provider is not exist.');
        } elseif ($this->getKey() !== $paymentProvider->business_id) {
            throw new WalletException(sprintf('The payment provider ID: %s is not belongs to this business ID: %s.',
                $paymentProvider->getKey(), $this->getKey()));
        }

        if (in_array($paymentProvider->payment_provider, [
            Enumerations\PaymentProvider::STRIPE_MALAYSIA,
            Enumerations\PaymentProvider::STRIPE_US
        ])) {
            $outgoingDescription = 'Paid out to ' .
                Enumerations\PaymentProvider::displayName($paymentProvider->payment_provider) .
                ' (Account No: ' . $paymentProvider->payment_provider_account_id . ')';

            $meta = [
                'account_no' => $paymentProvider->payment_provider_account_id,
            ];
        } else {
            throw new WalletException(sprintf('The payment provider \'%s\' is currently not supported.',
                $paymentProvider->payment_provider));
        }

        $availableWallet = $this->availableBalance($currency);

        if ($availableWallet->balance <= 0) {
            throw new WalletException('The wallet has insufficient balance.');
        } elseif (is_null($amount)) {
            $amount = $availableWallet->balance;
        } elseif ($availableWallet->balance - $amount < 0) {
            throw new WalletException('Insufficient balance for payout.');
        }

        $lastPayout = $availableWallet->transactions()
            ->where('event', WalletEnums\Event::PAID_TO_BANK)
            ->orderByDesc('created_at')
            ->first();

        if ($lastPayout instanceof Wallet\Transaction) {
            $transactionsToBeAttached = $availableWallet->transactions()
                ->where('created_at', '>=', $lastPayout->created_at)
                ->get();
        } else {
            $transactionsToBeAttached = $availableWallet->transactions()->get();
        }

        $transfer = new Transfer;

        $transfer->payment_provider_transfer_method = 'wallet_' . $paymentProvider->payment_provider;
        $transfer->payment_provider = $paymentProvider->payment_provider;
        $transfer->payment_provider_account_id = $paymentProvider->payment_provider_account_id;
        $transfer->currency = $currency;
        $transfer->amount = $amount;
        $transfer->remark = 'HitPay balance payouts for '.Facades\Date::now()->toDateString();

        $transfer->data = [
            'payment_provider' => $paymentProvider->data,
        ];

        $transfer->status = 'request_pending';

        Facades\DB::beginTransaction();

        try {
            $this->transfers()->save($transfer);

            if ($transactionsToBeAttached->count()) {
                $transfer->transactions()->attach($transactionsToBeAttached->pluck('id'));
            }

            $availableWallet->outgoing(
                WalletEnums\Event::PAID_TO_BANK,
                $amount,
                $outgoingDescription,
                $meta ?? [],
                $transfer,
                null,
                null,
                false
            );

            Facades\DB::commit();
        } catch (Throwable $exception) {
            Facades\DB::rollBack();

            throw $exception;
        }

        return $transfer;
    }

    public function administrativeDeduction(
        string $currency, int $amount, string $outgoingDescription = 'Administration Deduction'
    ) : Wallet\Transaction {
        if ($amount <= 0) {
            throw new WalletException('The amount must be at least 1.');
        }

        $availableWallet = $this->availableBalance($currency);

        if ($amount > $availableWallet->balance) {
            throw new WalletException("The wallet has insufficient balance, available balance: {$availableWallet->balance}.");
        }

        Facades\DB::beginTransaction();

        try {
            $transaction = $availableWallet->outgoing(
                WalletEnums\Event::ADMINISTRATIVE_DEDUCTION,
                $amount,
                $outgoingDescription
            );

            Facades\DB::commit();
        } catch (Throwable $exception) {
            Facades\DB::rollBack();

            throw $exception;
        }

        return $transaction;
    }

    public function administrativeTopUp(
        string $currency, int $amount, string $outgoingDescription = 'Administration Top Up'
    ) : Wallet\Transaction {
        if ($amount <= 0) {
            throw new WalletException('The amount must be at least 1.');
        }

        $availableWallet = $this->availableBalance($currency);

        Facades\DB::beginTransaction();

        try {
            $transaction = $availableWallet->incoming(
                WalletEnums\Event::ADMINISTRATIVE_TOP_UP,
                $amount,
                $outgoingDescription
            );

            Facades\DB::commit();
        } catch (Throwable $exception) {
            Facades\DB::rollBack();

            throw $exception;
        }

        return $transaction;
    }

    public function topUp(string $currency, int $amount, string $incomingDescription = 'Top Up') : Wallet\Transaction
    {
        if ($amount <= 0) {
            throw new WalletException('The amount must be at least 1.');
        }

        $reserveWallet = $this->reserveBalance($currency);

        Facades\DB::beginTransaction();

        try {
            $transaction = $reserveWallet->incoming(WalletEnums\Event::TOP_UP, $amount, $incomingDescription);

            Facades\DB::commit();
        } catch (Throwable $exception) {
            Facades\DB::rollBack();

            throw $exception;
        }

        return $transaction;
    }

    /**
     * Withdraw from wallet for refund.
     *
     * @param \App\Business\Charge $charge
     * @param int|null $amount
     *
     * @return \App\Business\Refund
     * @throws \App\Exceptions\InsufficientFund
     * @throws \App\Exceptions\WalletException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function withdrawForRefund(Charge $charge, int $amount = null) : Refund
    {
        if (!$charge->exists) {
            throw new WalletException(sprintf('Charge ID: %s does not exist.', $charge->getKey()));
        } elseif ($this->getKey() !== $charge->business_id) {
            throw new WalletException(sprintf('The charge ID: %s is not belongs to this business ID: %s.',
                $charge->getKey(), $this->getKey()));
        } elseif ($charge->status !== ChargeStatus::SUCCEEDED) {
            throw new WalletException(sprintf('Unsupported charge ID: %s with status \'%s\' to do refund. The status must be \'succeeded\'.',
                $charge->getKey(), $charge->status));
        } elseif (!$this->isValidCharge($charge, true)) {
            throw new WalletException(sprintf('Unsupported charge ID: %s via payment provider \'%s\' for wallet to refund.',
                $charge->getKey(), $charge->payment_provider));
        }

        // Refund cannot be performed on transactions older than 30 days
        if ($charge->created_at->format('Y-m-d H:i:s') < Carbon::now()->subDays(30)->format('Y-m-d H:i:s')) {
            throw new WalletException('Refund cannot be performed on transactions older than 30 days.');
        }

        if ($amount <= 0) {
            throw new WalletException('The amount to be refunded cannot equal to or less than zero.');
        }

        // Currently we allow PayNow and collection to be refunded via wallet, no matter the charge is made before or
        // after the wallet implementation.

        $refundableAmount = $charge->balance ?? $charge->amount;
        $amountToBeRefundedCalculation = $amountToBeRefunded = $amount ?? $refundableAmount;

        if ($refundableAmount <= 0) {
            throw new WalletException(sprintf('Charge ID: %s does not have any balance to refund.', $charge->getKey()));
        }

        // Let's check balance in all balances.

        $currency = $charge->currency;

        /** @var \App\Business\Refund $refund */
        $refund = $charge->refunds()->make([
            'payment_provider' => $charge->payment_provider,
            'payment_provider_account_id' => null,
            'payment_provider_refund_method' => 'wallet',
            'amount' => $amountToBeRefunded,
            'status' => 'pending',
        ]);

        $balanceCanBeRefunded = (int) $this->wallets()->whereIn('type', [
            WalletEnums\Type::AVAILABLE,
            WalletEnums\Type::RESERVE,
        ])->where('currency', $currency)->sum('balance');

        if ($balanceCanBeRefunded < $amountToBeRefunded) {
            throw new InsufficientFund(sprintf('Insufficient balance to refund. You can refund maximum up to %s now. <a href="https://hitpay.zendesk.com/hc/en-us/articles/4404389273113-How-do-I-top-up-my-HitPay-Balance-" style="text-decoration: underline;" target="_blank">Top up your HitPay balance to refund.</a>',
                getFormattedAmount($currency, $balanceCanBeRefunded)));
        }

        if ($refundableAmount - $amountToBeRefunded <= 0) {
            $charge->status = ChargeStatus::REFUNDED;
            $charge->balance = null;
            $charge->refunded_at = $charge->freshTimestamp();
        } else {
            $charge->balance = $refundableAmount - $amountToBeRefunded;
        }

        Facades\DB::beginTransaction();

        $refund->save();

        try {
            $sequence = 0;

            foreach ([
                ['type' => WalletEnums\Type::AVAILABLE, 'force' => false],
                ['type' => WalletEnums\Type::RESERVE, 'force' => false],
                ['type' => WalletEnums\Type::DEPOSIT, 'force' => false],
                ['type' => WalletEnums\Type::AVAILABLE, 'force' => true],
            ] as $configuration) {
                if ($amountToBeRefundedCalculation <= 0) {
                    break;
                }

                $wallet = $this->wallet($configuration['type'], $currency);

                if ($configuration['force']) {
                    $amountCanBeRefunded = $amountToBeRefundedCalculation;
                } else {
                    if ($wallet->balance <= 0) {
                        continue;
                    }

                    if ($wallet->balance >= $amountToBeRefundedCalculation) {
                        $amountCanBeRefunded = $amountToBeRefundedCalculation;
                    } else {
                        $amountCanBeRefunded = $wallet->balance;
                    }

                    if ($amountToBeRefunded === 0) {
                        continue;
                    }
                }

                $amountToBeRefundedCalculation = (int) bcsub($amountToBeRefundedCalculation, $amountCanBeRefunded);

                $sequence++;

                $transaction = $wallet->outgoing(
                    WalletEnums\Event::WITHDREW_FOR_REFUND,
                    $amountCanBeRefunded,
                    'Refunded to charge # '.$charge->getKey(),
                    [
                        'charge' => [
                            'id' => $charge->getKey(),
                            'currency' => $charge->currency,
                            'amount' => $charge->amount,
                            'amount_text' => getReadableAmountByCurrency($charge->currency, $charge->amount),
                            'fee' => $charge->getTotalFee(),
                            'fee_text' => getReadableAmountByCurrency($charge->currency, $charge->getTotalFee()),
                        ],
                        'refund_intent' => [
                            'id' => $refund->id,
                            'amount' => $refund->amount,
                            'amount_text' => getReadableAmountByCurrency($charge->currency, $refund->amount),
                        ],
                    ],
                    $refund,
                    null,
                    $sequence
                );

                $transactions[] = $transaction;
                $refundData[] = $transaction->toArray();
            }

            $refund->data = [ 'transactions' => $refundData ?? [] ];

            $charge->save();
            $refund->save();

            Facades\DB::commit();
        } catch (Throwable $exception) {
            Facades\DB::rollBack();

            throw $exception;
        }

        Jobs\Wallet\Refund::dispatch($refund)->onQueue('main-server');

        try {
             SubmitChargeForMonitoring::dispatch($charge, $charge->business, $refund);
        }catch (Throwable $exception){
            Facades\Log::critical("Dispatch job to submit charge for monitoring #{$charge->getKey()} failed. Error: {$exception->getMessage()} ({$exception->getFile()}:{$exception->getLine()})");
        }

        return $refund;
    }

    /**
     * Withdraw from wallet for refund.
     *
     * @param \App\Business\Charge $charge
     * @param int|null $amount
     *
     * @return \App\Business\Refund
     * @throws \App\Exceptions\InsufficientFund
     * @throws \App\Exceptions\WalletException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function withdrawForCampaignRefund(Charge $charge, int $amount = null) : Refund
    {
        if (!$charge->exists) {
            throw new WalletException(sprintf('Charge ID: %s does not exist.', $charge->getKey()));
        } elseif ($charge->status !== ChargeStatus::SUCCEEDED) {
            throw new WalletException(sprintf('Unsupported charge ID: %s with status \'%s\' to do refund. The status must be \'succeeded\'.',
                $charge->getKey(), $charge->status));
        } elseif (!$this->isValidCharge($charge, true)) {
            throw new WalletException(sprintf('Unsupported charge ID: %s via payment provider \'%s\' for wallet to refund.',
                $charge->getKey(), $charge->payment_provider));
        }

        if ($charge->payment_provider !== Enumerations\PaymentProvider::DBS_SINGAPORE) {
            throw new WalletException('Currently only payment made via PayNow or direct debit are supported.');
        }

        if ($amount <= 0) {
            throw new WalletException('The amount to be refunded cannot equal to or less than zero.');
        }

        $amountToBeRefundedCalculation = $amountToBeRefunded = $amount;

        // Let's check balance in all balances.

        $currency = $charge->currency;

        /** @var \App\Business\Refund $refund */
        $refund = $charge->refunds()->make([
            'payment_provider' => Enumerations\PaymentProvider::DBS_SINGAPORE,
            'payment_provider_account_id' => null,
            'payment_provider_refund_method' => 'wallet',
            'amount' => $amount,
            'status' => 'pending',
        ]);

        $balanceCanBeRefunded = (int) $this->wallets()->whereIn('type', [
            WalletEnums\Type::AVAILABLE,
            WalletEnums\Type::RESERVE,
        ])->where('currency', $currency)->sum('balance');

        if ($balanceCanBeRefunded < $amount) {
            throw new InsufficientFund(sprintf('Insufficient balance to refund. You can refund maximum up to %s now.',
                getFormattedAmount($currency, $balanceCanBeRefunded)));
        }

        Facades\DB::beginTransaction();

        $refund->save();

        try {
            $sequence = 0;

            foreach ([
                         ['type' => WalletEnums\Type::AVAILABLE, 'force' => false],
                         ['type' => WalletEnums\Type::RESERVE, 'force' => false],
                         ['type' => WalletEnums\Type::DEPOSIT, 'force' => false],
                         ['type' => WalletEnums\Type::AVAILABLE, 'force' => true],
                     ] as $configuration) {
                if ($amountToBeRefundedCalculation <= 0) {
                    break;
                }

                $wallet = $this->wallet($configuration['type'], $currency);

                if ($configuration['force']) {
                    $amountCanBeRefunded = $amountToBeRefundedCalculation;
                } else {
                    if ($wallet->balance <= 0) {
                        continue;
                    }

                    if ($wallet->balance >= $amountToBeRefundedCalculation) {
                        $amountCanBeRefunded = $amountToBeRefundedCalculation;
                    } else {
                        $amountCanBeRefunded = $wallet->balance;
                    }

                    if ($amountToBeRefunded === 0) {
                        continue;
                    }
                }

                $amountToBeRefundedCalculation = (int) bcsub($amountToBeRefundedCalculation, $amountCanBeRefunded);

                $sequence++;

                $transaction = $wallet->outgoing(
                    WalletEnums\Event::WITHDREW_FOR_REFUND,
                    $amountCanBeRefunded,
                    'Refunded to charge # '.$charge->getKey(),
                    [
                        'charge' => [
                            'id' => $charge->getKey(),
                            'currency' => $charge->currency,
                            'amount' => $charge->amount,
                            'amount_text' => getReadableAmountByCurrency($charge->currency, $charge->amount),
                            'fee' => $charge->getTotalFee(),
                            'fee_text' => getReadableAmountByCurrency($charge->currency, $charge->getTotalFee()),
                        ],
                        'refund_intent' => [
                            'id' => $refund->id,
                            'amount' => $refund->amount,
                            'amount_text' => getReadableAmountByCurrency($charge->currency, $refund->amount),
                        ],
                    ],
                    $refund,
                    null,
                    $sequence
                );

                $transactions[] = $transaction;
                $refundData[] = $transaction->toArray();
            }

            $refund->data = [ 'transactions' => $refundData ?? [] ];

            $charge->save();
            $refund->save();

            Facades\DB::commit();
        } catch (Throwable $exception) {
            Facades\DB::rollBack();

            throw $exception;
        }

        Jobs\Wallet\Refund::dispatch($refund)->onQueue('main-server');
        try {
             SubmitChargeForMonitoring::dispatch($charge, $charge->business, $refund);
        }catch (Throwable $exception){
            Facades\Log::critical("Dispatch job to submit charge for monitoring #{$charge->getKey()} failed. Error: {$exception->getMessage()} ({$exception->getFile()}:{$exception->getLine()})");
        }

        return $refund;
    }

    public function withdrawForChargeback(Charge $charge, int $amount = null) : void
    {
        // TODO - IMPORTANT
        //   -
        //   Withdraw the fund from wallet with fund. We are not having chargeback yet. Will have it in phase 2.
    }

    /**
     * Check if the charge is a valid DBS or Shopee charge.
     *
     * @param \App\Business\Charge $charge
     *
     * @return bool
     */
    private function isValidCharge(Charge $charge, bool $refund) : bool
    {
        switch ($charge->payment_provider) {
          case Enumerations\PaymentProvider::DBS_SINGAPORE:
            if ($charge->payment_provider_charge_type === 'inward_credit_notification') {
                return $charge->payment_provider_charge_method === PaymentMethodType::PAYNOW;
            } elseif ($charge->payment_provider_charge_type === PaymentMethodType::COLLECTION) {
                return $charge->payment_provider_charge_method === 'direct_debit';
            } elseif (Str::startsWith($charge->payment_provider_charge_id, 'DICN')) {
                // By Bankorh:
                // I remembered there's a mistake that if the charge is paid using PayNow, but the charge type is not
                // `inward_credit_notification`, hence if "DICN" prefix detected, I will treat it as PayNow.
                //
                return true;
            }

            return false;

          case Enumerations\PaymentProvider::SHOPEE_PAY:
          case Enumerations\PaymentProvider::GRABPAY:
          case Enumerations\PaymentProvider::ZIP:
            return $refund
              ? $charge->canRefund()
              : true;

          default:
            return false;
        }
    }
}
