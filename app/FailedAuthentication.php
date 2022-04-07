<?php

namespace App;

use HitPay\User\Ownable;
use HitPay\Agent\LogHelpers;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class FailedAuthentication extends Model
{
    use LogHelpers, Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'failed_authentications';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'logged_at' => 'datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'reason',
    ];

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
}
