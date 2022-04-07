<?php

namespace App;

use App\Facades\Agent;
use HitPay\Agent\LogHelpers;
use HitPay\Model\UsesUuid;
use HitPay\User\Contracts\Ownable as OwnableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Session extends Model implements OwnableContract
{
    use LogHelpers, SoftDeletes, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sessions';

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'is_revoked',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'value',
        'remember_token',
        'user_agent',
        'url',
        'data',
        'deleted_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'remember_token',
    ];

    /**
     * The indicator to not log executor.
     *
     * @var bool
     */
    protected $logRequestWithExecutor = false;

    /**
     * The "booting" method of the model.
     */
    protected static function boot() : void
    {
        parent::boot();

        static::creating(function (self $model) : void {
            $model->setAttribute('value', Str::random(100));
            $model->setAttribute('remark', $model->getAgentInstance()->deviceName());
        });
    }

    /**
     * Get mutator for is revoked attribute.
     *
     * @return bool
     */
    public function getIsRevokedAttribute() : bool
    {
        return $this->trashed();
    }

    public function isRevoked() : bool
    {
        return $this->is_revoked;
    }

    /**
     * Get the name of the "remember token" for session.
     *
     * @return string
     */
    public function getRememberTokenName() : string
    {
        return 'remember_token';
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string|null
     */
    public function getRememberToken() : ?string
    {
        return $this->{$this->getRememberTokenName()};
    }

    /**
     * Get the owner of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\User
     */
    public function owner() : BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id', 'id', 'owner');
    }

    /**
     * @return bool|null
     * @throws \Exception
     */
    public function revoke()
    {
        return $this->delete();
    }
}
