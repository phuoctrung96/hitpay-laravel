<?php

namespace HitPay\Verification\Cognito\FlowSession;

use App\Business;
use App\Enumerations\VerificationProvider;
use Exception;
use HitPay\Verification\Cognito\Service;

class Create extends Service
{
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
        // https://cognitohq.com/docs/reference#create_flow_session-query_param-idempotent
        $requestString = '/flow_sessions?idempotent=false';

        $requestTarget = 'post /flow_sessions?idempotent=false';

        $owner = $this->business->owner()->first();

        $bodyParams = [
            'shareable' => true,
            'template_id' => $this->templateId,
            'consent' => true,
            'user' => [
                'customer_reference' => $this->business->getKey(),
                'email' => $owner->email,
                'name' => [
                    "first" => $owner->first_name ?? null,
                    "last" => $owner->last_name ?? null,
                ],
                'phone' => $this->business->phone_number ?: null
            ]
        ];

        $cognitoData = $this->setUri($requestString)
            ->setMethod('post')
            ->setRequestTarget($requestTarget)
            ->setBodyParams($bodyParams)
            ->generateAuthenticationHeader()
            ->process();

        if ($cognitoData == null) {
            throw new \Exception("Creating cognito data failed from business " . $this->business->getKey());
        }

        return Business\Verification::create([
            'business_id' => $this->business->getKey(),
            'verification_provider' => VerificationProvider::COGNITO,
            'verification_provider_account_id' => $cognitoData['id'],
            'verification_provider_status' => $cognitoData['status'],
            'cognitohq_data' => $cognitoData,
            'status' => '',
            'name' => $cognitoData['user']['name']['first'] . ' ' . $cognitoData['user']['name']['last'],
            'type' => $this->business->business_type == 'company' ? 'business' : 'personal',
        ]);
    }
}
