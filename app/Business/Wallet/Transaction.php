<?php

namespace App\Business\Wallet;

use App\Business;
use App\Business\Wallet;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\URL;

class Transaction extends Model
{
    use UsesUuid;

    /**
     * @inheritdoc
     */
    protected $table = 'business_wallet_transactions';

    /**
     * @inheritdoc
     */
    protected $guarded = [];

    /**
     * @inheritdoc
     */
    protected $casts = [
        'amount' => 'int',
        'balance_before' => 'int',
        'balance_after' => 'int',
        'sequence' => 'int',
        'meta' => 'array',
        'timeline' => 'array',
    ];

    /**
     * The business of this transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function business() : BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id', 'id', 'business');
    }

    /**
     * The wallet of this transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function wallet() : BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id', 'id', 'wallet');
    }

    /**
     * The wallet of this transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function relatedWallet() : BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'related_wallet_id', 'id', 'wallet');
    }

    /**
     * Get the relatable model for the transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function relatable() : MorphTo
    {
        return $this->morphTo('relatable', 'relatable_type', 'relatable_id');
    }

    /**
     * Get the involving wallet transactions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function walletTransactions() : MorphMany
    {
        return $this->morphMany(Wallet\Transaction::class, 'relatable', null, null, 'id');
    }

    /**
     * Revert the transaction.
     *
     * @return $this
     * @throws \Exception
     */
    public function revertForOutgoing() : self
    {
        return $this->wallet->revertForOutgoing($this);
    }

    /**
     * Get button text for relatable.
     *
     * @return string|null
     */
    public function getRelatableButtonText() : ?string
    {
        switch (true) {
            case $this->relatable instanceof Business\Charge:
                return 'View Charge';
        }

        return null;
    }

    /**
     * Get URL for relatable.
     *
     * @param string $path
     *
     * @return string|null
     */
    public function getRelatableUrl(string $path = 'dashboard') : ?string
    {
        switch (true) {
            case $this->relatable instanceof Business\Charge:
                return URL::route("$path.business.charge.show", [$this->business_id, $this->relatable->getKey()]);
        }

        return null;
    }

    /**
     * Helper to append to timeline.
     *
     * @param string $content
     */
    public function addTimeline(string $content)
    {
        $timeline = $this->timeline ?? [];

        $timeline[] = $this->freshTimestamp()->toDateTimeString('millisecond').' - '.$content;

        $this->timeline = $timeline;
    }
}
