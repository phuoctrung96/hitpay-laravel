<?php

namespace HitPay\User;

use App\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait Ownable
{
    /**
     * Get the owner of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\User
     */
    public function owner() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'owner');
    }
}
