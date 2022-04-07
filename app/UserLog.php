<?php

namespace App;

use HitPay\Agent\LogHelpers;
use HitPay\Model\UsesUuid;
use HitPay\User\Ownable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserLog extends Model
{
    use LogHelpers, Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_logs';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'logged_at' => 'datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        //
    ];

    /**
     * The attributes name to be used for request logging.
     *
     * @var string
     */
    protected $logRequestAttributesName = 'data';

    /**
     * The "booting" method of the model.
     */
    protected static function boot() : void
    {
        parent::boot();

        static::creating(function (self $model) : void {
            if (!isset($model->attributes['logged_at'])) {
                $model->setAttribute('logged_at', $model->freshTimestamp());
            }
        });
    }

    /**
     * Get the associable of the account log.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function associable() : MorphTo
    {
        return $this->morphTo('associable', 'associable_type', 'associable_id');
    }

    /**
     * Get the executor of the account log.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function executor() : BelongsTo
    {
        return $this->belongsTo(User::class, 'executor_id', 'id', 'executor');
    }
}
