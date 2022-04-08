<?php

namespace App\Actions\Business\Settings\Verification\Cognito;

use Exception;
use Illuminate\Support\Facades;

class Show extends Action
{
    /**
     * @return array
     * @throws Exception
     */
    public function process() : array
    {
        $verificationStatusTitle = "Verification Pending";

        if ($this->verification->isVerified()) {
            $verificationStatusTitle = "Verification Completed";
        }

        $verificationStatus = $this->verification->status != '' ? 'completed' : 'manual';

        if ($verificationStatus == 'completed') {
            $verificationStatusTitle = 'Verification Submitted';
            $verification_data = $this->verification->verificationData('submitted');
        } else {
            $verification_data = $this->verification->verificationData('cognito');
        }

        $type = $this->verification->type === 'business' ? 'company' : 'individual';

        if ($type == 'company') {
            $verification_data = $this->setShareholderData($this->verification, $verification_data);
        }

        $businessUser = $this->business->businessUsers();

        $businessUser = $businessUser
            ->where('user_id', Facades\Auth::id())
            ->first();

        $isOwner = $businessUser->isOwner();

        if ($isOwner) {
            $businessUserOwner = $businessUser;
        } else {
            $businessUserOwner = $businessUser->getUserOwner();
        }

        return [
            'business' => $this->business,
            'verificationStatus' => $verificationStatus,
            'verification_data' => $verification_data,
            'verification' => $this->verification,
            'type' => $type,
            'isOwner' => $isOwner,
            'businessUserOwner' => $businessUserOwner,
            'verificationStatusTitle' => $verificationStatusTitle,
        ];
    }
}
