<?php

namespace App\Business;

use App\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessReferralPayout extends Model
{
    protected $guarded = [];

    public function business():BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function referredBusiness():BelongsTo
    {
        return $this->belongsTo(Business::class, 'referred_business_id');
    }
}
