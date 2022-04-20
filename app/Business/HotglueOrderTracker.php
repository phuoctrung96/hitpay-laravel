<?php

namespace App\Business;

use Illuminate\Database\Eloquent\Model;

class HotglueOrderTracker extends Model
{
    protected $guarded = [];

    public function hotglueJob()
    {
        return $this->belongsTo('App\Business\HotglueJob', 'hotglue_job_id', 'id', 'hotglue_jobs');
    }
}
