<?php

namespace App\Business;

use App\User;
use HitPay\Agent\LogHelpers;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Log extends Model
{
    use LogHelpers, Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_logs';

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
     * Get the associable of the log.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo|\Illuminate\Database\Eloquent\Model
     */
    public function associable() : MorphTo
    {
        return $this->morphTo('associable', 'business_associable_type', 'business_associable_id');
    }

    /**
     * Get the executor of the log.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\User
     */
    public function executor() : BelongsTo
    {
        return $this->belongsTo(User::class, 'executor_id', 'id', 'executor');
    }

    /**
     * Get the relatable of the log.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function relatable() : MorphTo
    {
        return $this->morphTo('relatable', 'relatable_type', 'relatable_id');
    }
}
