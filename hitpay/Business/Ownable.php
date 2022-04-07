<?php

namespace HitPay\Business;

use App\Business;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait Ownable
{
    /**
     * Get the business of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business
     */
    public function business() : BelongsTo
    {
        // We remove all the registered global scopes here, because we want to be able to retrieve the owner of an
        // item no matter what is the status of the owner.
        //
        return $this->belongsTo(Business::class, 'business_id', 'id', 'business')->withoutGlobalScopes();
    }
}
