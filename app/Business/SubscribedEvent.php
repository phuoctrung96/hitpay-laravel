<?php

namespace App\Business;

use Illuminate\Database\Eloquent\Model;

class SubscribedEvent extends Model
{
    protected $table = 'business_subscribed_events';

    protected $guarded = [];

    public $timestamps = false;
}
