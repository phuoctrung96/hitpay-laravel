<?php

namespace App\Business;

use App\Business\HotglueJob;
use App\Business\HotglueLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HotglueIntegration extends Model
{
    protected $guarded = [];

    public function business()
    {
        return $this->belongsTo('App\Business', 'business_id', 'id', 'businesses');
    }

    public function jobInProgressJobCreated(): HasMany
    {
        return $this->hasMany(HotglueJob::class, 'hotglue_integration_id', 'id')
            ->where('status', HotglueJob::CREATED)
            ->whereIn('job_type', [HotglueJob::INITIAL_SYNC, HotglueJob::NOW_SYNC])
            ->orderByDesc('id')
            ->limit(1);
    }

    public function jobInProgressJobQueued(): HasMany
    {
        return $this->hasMany(HotglueJob::class, 'hotglue_integration_id', 'id')
            ->where('status', HotglueJob::QUEUED)
            ->whereIn('job_type', [HotglueJob::INITIAL_SYNC, HotglueJob::NOW_SYNC])
            ->orderByDesc('id')
            ->limit(1);
    }

    public function jobDone(): HasMany
    {
        return $this->hasMany(HotglueJob::class, 'hotglue_integration_id', 'id')
            ->whereNotIn('status', [HotglueJob::CREATED, HotglueJob::QUEUED])
            ->whereIn('job_type', [HotglueJob::INITIAL_SYNC, HotglueJob::NOW_SYNC])
            ->orderByDesc('id')
            ->limit(1);
    }

    public function hotglueLocation()
    {
        return $this->hasMany(HotglueLocation::class, 'hotglue_integration_id', 'id');
    }
}
