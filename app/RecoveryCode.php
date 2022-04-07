<?php

namespace App;

use HitPay\Model\UsesUuid;
use HitPay\User\Contracts\Ownable as OwnableContract;
use HitPay\User\Ownable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecoveryCode extends Model implements OwnableContract
{
    use Ownable, SoftDeletes, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'recovery_codes';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'used_at' => 'datetime',
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
        'code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'user_id',
        'deleted_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'is_used',
    ];

    /**
     * Get mutator for "is used" attribute.
     *
     * @return bool
     */
    public function getIsUsedAttribute()
    {
        return !is_null($this->used_at);
    }

    /**
     * Set the keys for a save update query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        return $query->where('user_id', '=', $this->original['user_id'])->where('code', '=', $this->original['code']);
    }

    /**
     * Mark the recovery code as used.
     *
     * @return bool
     */
    public function use() : bool
    {
        $this->used_at = $this->freshTimestamp();

        $this->setKeysForSaveQuery($this->newModelQuery())->update([
            'used_at' => $this->getAttributeFromArray('used_at'),
        ]);

        return true;
    }
}
