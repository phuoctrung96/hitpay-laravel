<?php

namespace HitPay\Business\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface Ownable
{
    /**
     * Get the business of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business
     */
    public function business() : BelongsTo;
}
