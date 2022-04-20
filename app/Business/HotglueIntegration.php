<?php

namespace App\Business;

use App\Business\HotglueJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HotglueIntegration extends Model
{
    protected $guarded = [];

    public function business()
    {
        return $this->belongsTo('App\Business', 'business_id', 'id', 'businesses');
    }

    public function jobInProgress(): HasMany
    {
        return $this->hasMany(HotglueJob::class, 'hotglue_integration_id', 'id')->whereStatus(HotglueJob::CREATED);
    }
}
