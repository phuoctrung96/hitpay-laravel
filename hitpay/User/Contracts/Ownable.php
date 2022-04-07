<?php

namespace HitPay\User\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface Ownable
{
    /**
     * Get the owner of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\User
     */
    public function owner() : BelongsTo;
}
