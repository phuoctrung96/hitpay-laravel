<?php

namespace HitPay\Verification\Cognito\FlowSession;

use App\Business;
use App\Enumerations\VerificationProvider;
use Exception;
use HitPay\Verification\Cognito\Service;

class Retry extends Service
{
    protected Business $business;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return Business\Verification
     * @throws Exception
     */
    public function handle() : Business\Verification
    {
        // https://cognitohq.com/docs/reference#flow_retry_flow_session
        $requestString = '/flow_sessions/retry';

        $requestTarget = 'post /flow_sessions/retry';

        $bodyParams = [
            'customer_reference' => $this->business->getKey(),
            'template_id' => $this->templateId,
            'strategy' => 'reset', // reset all data, have 4 option (https://cognitohq.com/docs/reference#retry_flow_session-request_body-strategy)
        ];

        $cognitoData = $this->setUri($requestString)
            ->setMethod('post')
            ->setRequestTarget($requestTarget)
            ->setBodyParams($bodyParams)
            ->generateAuthenticationHeader()
            ->process();

        if ($cognitoData == null) {
            throw new \Exception("Retrying cognito data failed from business " . $this->business->getKey());
        }

        $verification = $this->business->verifications()->latest()->first();

        $verification = $verification->update([
            'verification_provider' => VerificationProvider::COGNITO,
            'verification_provider_account_id' => $cognitoData['id'],
            'verification_provider_status' => $cognitoData['status'],
            'cognitohq_data' => $cognitoData,
            'status' => '',
            'name' => '',
            'type' => $this->business->business_type == 'company' ? 'business' : 'personal',
        ]);

        return $verification->refresh();
    }
}
