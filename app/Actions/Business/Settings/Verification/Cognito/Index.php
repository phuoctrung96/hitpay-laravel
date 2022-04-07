<?php

namespace App\Actions\Business\Settings\Verification\Cognito;

use Exception;
use HitPay\Verification\Cognito\FlowSession\Retrieve;
use Illuminate\Support\Facades;

class Index extends Action
{
    /**
     * @return array
     * @throws Exception
     */
    public function process() : array
    {
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

        $cognitoFlow = new Retrieve();
        $customerSignature = $cognitoFlow->getCustomerSignature($this->business);

        $verificationProvider = [
            'verificationProviderName' => 'cognito',
            'publishableKey' => Facades\Config::get('services.cognito.publishable_key'),
            'templateId' => Facades\Config::get('services.cognito.template_id'),
            'production_ready' => Facades\Config::get('services.cognito.production_ready'),
            'customerSignature' => $customerSignature,
        ];

        return [
            'business' => $this->business,
            'verificationProvider' => $verificationProvider,
            'isOwner' => $isOwner,
            'businessUserOwner' => $businessUserOwner
        ];
    }
}
