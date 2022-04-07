<?php

namespace App\Listeners;

use App\Events\Business\Created;
use App\Notifications\RegistrationInviteAccepted;
use App\Notifications\Welcome;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWelcomeEmail implements ShouldQueue
{
    /**
     * @param \App\Events\Business\Created $event
     */
    public function handle(Created $event)
    {
        $event->business->notify(new Welcome());
        if($event->business->referredBy) {
            $event->business->referredBy->business->notify(new RegistrationInviteAccepted($event->business));
        }
    }
}
