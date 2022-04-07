<?php

namespace App;

use Carbon\Carbon;
use HitPay\Model\UsesUuid;
use HitPay\User\Ownable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class PaymentCard extends Model
{
    use Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_cards';

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
        'name',
        'last_4',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot() : void
    {
        parent::boot();

        static::created(function (self $model) : void {
            $model->createLog('added', $model->getAttribute('created_at'),
                Arr::only($model->getAttributes(), $model->loggableAttributes));
        });

        static::deleted(function (self $model) : void {
            $model->createLog('removed', $model->freshTimestamp(),
                Arr::only($model->getOriginal(), $model->loggableAttributes));
        });
    }

    /**
     * Create log for the role.
     *
     * @param string $event
     * @param \Illuminate\Support\Carbon $loggedAt
     * @param array|null $attributes
     *
     * @throws \Exception
     */
    protected function createLog(string $event, Carbon $loggedAt, array $attributes = null) : void
    {
        $log = UserLog::make([
            'user_id' => $this->user_id,
            'group' => 'billing',
            'event' => $event,
            'logged_at' => $loggedAt,
        ]);

        $log->logAttributes($attributes);
        $log->associable()->associate($this);

        $log->save();
    }
}
