<?php

namespace App\Observers;

use App\Business;
use App\Business\BusinessReferral;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BusinessObserver
{
    /**
     * Handle the business "created" event.
     *
     * @param Business $business
     * @return void
     */
    public function created(Business $business)
    {

    }


}
