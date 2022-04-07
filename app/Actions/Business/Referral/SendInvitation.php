<?php

namespace App\Actions\Business\Referral;

use App\Notifications\BusinessReferralProgramInviteNotification;
use App\User;

class SendInvitation extends Action
{
    /**
     * @return bool
     */
    public function process() : bool
    {
        $user = new User();

        $user->email = $this->emailInvitation;

        $user->notify(new BusinessReferralProgramInviteNotification($this->business));

        return true;
    }
}
