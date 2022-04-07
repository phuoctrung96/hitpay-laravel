<?php

namespace App\Business;

use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\LogHelpers;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class PaymentCard extends Model implements OwnableContract
{
    use LogHelpers, Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_payment_cards';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        //
    ];

    /**
     * The loggable attributes list.
     *
     * @var array
     */
    protected $loggableAttributes = [
        'payment_provider',
        'payment_provider_card_id',
        'name',
        'brand',
        'country',
        'funding',
        'fingerprint',
        'last_4',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot() : void
    {
        parent::boot();

        static::created(function (self $model) : void {
            $model->createLog('billing', 'added', $model->getAttribute('created_at'),
                Arr::only($model->getAttributes(), $model->loggableAttributes));
        });

        static::deleted(function (self $model) : void {
            $model->createLog('billing', 'removed', $model->freshTimestamp(),
                Arr::only($model->getOriginal(), $model->loggableAttributes));
        });
    }
}
