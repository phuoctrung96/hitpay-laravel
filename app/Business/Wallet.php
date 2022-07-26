<?php

namespace App\Business;

use App\Business;
use App\Enumerations\Business\Wallet as WalletEnums;
use App\Enumerations\CurrencyCode;
use App\Exceptions\WalletException;
use Exception;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades;
use Throwable;

final class Wallet extends Model
{
    use UsesUuid;

    /**
     * @inheritdoc
     */
    protected $table = 'business_wallets';

    /**
     * @inheritdoc
     */
    protected $guarded = [];

    /**
     * @inheritdoc
     */
    protected $casts = [
        'balance' => 'int',
        'reserve_balance' => 'int',
        'meta' => 'array',
        'last_cleared_at' => 'datetime',
    ];

    /**
     * The type which the wallet is supporting.
     *
     * @var array
     */
    private static $walletTypes = [
        WalletEnums\Type::PENDING,
        WalletEnums\Type::AVAILABLE,
        WalletEnums\Type::RESERVE,
        WalletEnums\Type::DEPOSIT,
    ];

    /**
     * @inheritdoc
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            if (!static::isSupportingType($model->type)) {
                throw new Exception('The wallet type \''.$model->type.'\' is invalid.');
            } elseif (!static::isSupportingCurrency($model->business, $model->currency)) {
                throw new Exception('The wallet currency \''.$model->currency.'\' is invalid.');
            }
        });

        static::saving(function (self $model) {
            if ($model->balance < 0) {
                $model->last_cleared_at = null;
            } elseif ($model->balance === 0 && $model->getOriginal('balance') > 0) {
                $model->last_cleared_at = $model->freshTimestamp();
            }
        });
    }

    /**
     * The business of this balance.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function business() : BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id', 'id', 'business');
    }

    public function topUpIntents() : HasMany
    {
        return $this->hasMany(Business\Wallet\TopUpIntent::class, 'business_wallet_id', 'id');
    }

    /**
     * The transactions of this wallet.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions() : HasMany
    {
        return $this->hasMany(Wallet\Transaction::class, 'wallet_id', 'id');
    }

    /**
     * Check if the giving type is supported.
     *
     * @param string $currency
     *
     * @return bool
     */
    public static function isSupportingType(string $type) : bool
    {
        return in_array($type, self::$walletTypes);
    }

    /**
     * Check if the giving currency is supported.
     *
     * @param string $currency
     *
     * @return bool
     */
    public static function isSupportingCurrency(Business $business, string $currency) : bool
    {
        return $business->currenciesAvailable()->contains($currency);
    }

    /**
     * Transfer fund from this wallet to another wallet.
     *
     * @param \App\Business\Wallet $wallet
     * @param int $amount
     * @param int|null $sequence
     * @param array $meta
     * @param \Illuminate\Database\Eloquent\Model|null $relatable
     * @param array $options
     * @param bool $forceTransfer
     *
     * @return \App\Business\Wallet\Transaction
     * @throws \App\Exceptions\WalletException
     * @throws \Throwable
     */
    public function transfer(
        Wallet $wallet, int $amount, int $sequence = null, array $meta = [], Model $relatable = null,
        array $options = [], bool $forceTransfer = false
    ) : Wallet\Transaction {
        if ($this->currency !== $wallet->currency) {
            throw new WalletException('Both wallets must have same currency to do transfer.');
        } elseif (!$forceTransfer && $this->balance < $amount) {
            throw new WalletException('Insufficient fund.');
        }

        Facades\DB::beginTransaction();

        try {
            $outgoingDescription = $options['outgoing_description'] ??
                'Transferred to \''.strtoupper($wallet->currency.' @ '.$wallet->type).'\'';

            $transaction = $this->outgoing(
                WalletEnums\Event::TRANSFERRED_TO_WALLET,
                $amount,
                $outgoingDescription,
                $meta,
                $relatable,
                $wallet,
                $sequence
            );

            $incomingDescription = $options['incoming_description'] ??
                'Received from \''.strtoupper($this->currency.' @ '.$this->type).'\'';

            $wallet->incoming(
                $options['incoming_event'] ?? WalletEnums\Event::RECEIVED_FROM_WALLET,
                $amount,
                $incomingDescription,
                $meta,
                $relatable,
                $this,
                $sequence
            );

            Facades\DB::commit();
        } catch (Throwable $exception) {
            Facades\DB::rollBack();

            throw $exception;
        }

        return $transaction;
    }

    /**
     * Revert a transaction.
     *
     * @param \App\Business\Wallet\Transaction $transaction
     *
     * @return \App\Business\Wallet\Transaction
     * @throws \Exception
     */
    public function revertForOutgoing(Business\Wallet\Transaction $transaction) : Business\Wallet\Transaction
    {
        if ($transaction->wallet_id !== $this->getKey()) {
            throw new WalletException(sprintf('The transaction ID: %s is not belongs to this wallet ID: %s.',
                $transaction->getKey(), $this->getKey()));
        }

        $revertEvent = $transaction->event.'_reverted';

        if ($transaction->walletTransactions()->where('event', $revertEvent)->count()) {
            throw new Exception(sprintf('The transaction ID # %s is already reverted.', $transaction->getKey()));
        }

        // TODO, if transfer transaction, we will have to revert BOTH.

        return $this->incoming($revertEvent, $transaction->amount,
            'Reverted: '.$transaction->description, $transaction->meta, $transaction);
    }

    public function outgoing(
        string $event, int $amount, string $description, array $meta = [], Model $relatable = null,
        Wallet $relatedWallet = null, int $sequence = null, bool $confirmed = true
    ) : Wallet\Transaction {
        return $this->transact($event, -1 * abs($amount), $description, $meta, $relatable, $relatedWallet, $sequence,
            $confirmed);
    }

    public function incoming(
        string $event, int $amount, string $description, array $meta = [], Model $relatable = null,
        Wallet $relatedWallet = null, $sequence = null, bool $confirmed = true
    ) : Wallet\Transaction {
        return $this->transact($event, abs($amount), $description, $meta, $relatable, $relatedWallet, $sequence,
            $confirmed);
    }

    public function transact(
        string $event, int $amount, string $description, array $meta = [], Model $relatable = null,
        Wallet $relatedWallet = null, $sequence = null, bool $confirmed = true
    ) : Wallet\Transaction {
        $wallet = static::query()->sharedLock()->find($this->getKey());

        if (!($wallet instanceof static)) {
            throw new Exception('The wallet is not exist.');
        }

        $transaction = new Wallet\Transaction;

        $transaction->business_id = $this->business_id;
        $transaction->event = $event;

        if (!is_null($relatedWallet)) {
            $transaction->relatedWallet()->associate($relatedWallet);
        }

        $transaction->balance_before = $wallet->balance;
        $transaction->amount = $amount;

        $wallet->balance = bcadd($wallet->balance, $transaction->amount);

        $transaction->balance_after = $wallet->balance;

        if (!is_null($relatable)) {
            $transaction->relatable()->associate($relatable);
        }

        $transaction->sequence = $sequence;
        $transaction->confirmed = $confirmed;
        $transaction->description = $description;

        if (count($meta)) {
            $transaction->meta = $meta;
        }

        $transaction->addTimeline($description);

        $wallet->transactions()->save($transaction);
        $wallet->save();

        return $transaction;
    }
}
