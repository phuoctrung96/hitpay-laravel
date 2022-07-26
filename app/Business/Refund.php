<?php

namespace App\Business;

use App\Business\Wallet\Transaction;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\Wallet\Event;
use Exception;
use HitPay\Agent\LogHelpers;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades;

class Refund extends Model implements OwnableContract
{
    use LogHelpers, Ownable, UsesUuid;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'business_refunds';

    protected $casts = [
        'data' => 'array',
    ];

    protected $guarded = [
        //
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function boot() : void
    {
        parent::boot();

        static::addGlobalScope('success', function (Builder $builder) : void {
            $builder->where(function (Builder $builder) : void {
                $builder->orWhereNull($builder->qualifyColumn('status'));
                $builder->orWhere($builder->qualifyColumn('status'), 'succeeded');
            });
        });
    }

    public function display($attribute)
    {
        switch ($attribute) {

            case 'amount':
                return getFormattedAmount($this->charge->currency, $this->amount);
        }

        throw new Exception('Invalid attribute.');
    }

    public function revert() : void
    {
        if ($this->payment_provider_refund_method !== 'wallet') {
            throw new Exception('Only wallet refund method can be reverted.');
        } elseif ($this->status !== 'failed') {
            throw new Exception('The failed status can be reverted.');
        }

        $charge = $this->charge;

        $transactions = Collection::make();

        $this->walletTransactions()->where('event', Event::WITHDREW_FOR_REFUND)->each(function (Transaction $transaction
        ) use (&$transactions) {
            $transactions->push($transaction->revertForOutgoing());
        });

        $this->status = 'reverted';

        Facades\DB::beginTransaction();

        $this->save();

        if ($charge->status !== ChargeStatus::REFUNDED && $charge->status !== ChargeStatus::SUCCEEDED) {
            Facades\Log::critical((sprintf('The reverted refund ID : %s is having an invalid status charge, charge ID : %s.',
                $this->getKey(), $charge->getKey())));
        }

        $charge->status = ChargeStatus::SUCCEEDED;

        if ($this->amount >= $charge->amount) {
            $charge->balance = null;
        } else {
            $charge->balance = $charge->balance + $this->amount;

            if ($charge->balance === $charge->amount) {
                $charge->balance = null;
            }
        }

        $charge->save();

        Facades\DB::commit();

        if ($transactions->sum('amount') !== $this->amount) {
            Facades\Log::critical((sprintf('The reverted refund ID : %s has not tally transactions sum.',
                $this->getKey())));
        }
    }

    /**
     * Get the charge of this refund.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business\Charge
     */
    public function charge() : BelongsTo
    {
        return $this->belongsTo(Charge::class, 'business_charge_id', 'id', 'charge');
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
}
