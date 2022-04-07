<?php

namespace HitPay\Verification\Cognito\FlowSession;

use App\Business;
use Exception;
use HitPay\Verification\Cognito\Service;
use Illuminate\Support\Facades;

class Retrieve extends Service
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $flowSessionId
     * @return mixed
     * @throws Exception
     */
    public function getFlowSessionById(string $flowSessionId)
    {
        // get flow session
        // https://cognitohq.com/docs/reference#flow_get_flow_session
        $requestString = '/flow_sessions/' . $flowSessionId;

        $requestTarget = 'get /flow_sessions/' . $flowSessionId;

        if (!Facades\App::environment('production')) {
            Facades\Log::info(print_r('requestString', true));
            Facades\Log::info(print_r($requestString, true));

            Facades\Log::info(print_r('requestTarget', true));
            Facades\Log::info(print_r($requestTarget, true));
        }

        $bodyParams = [];

        return $this->setUri($requestString)
            ->setMethod('get')
            ->setRequestTarget($requestTarget)
            ->setBodyParams($bodyParams)
            ->generateAuthenticationHeader()
            ->process();
    }

    /**
     * @param Business $business
     * @return string
     */
    public function getCustomerSignature(Business $business) : string
    {
        // https://cognitohq.com/docs/flow/securing-your-flow-integration
        $customerReference = $business->getKey();

        return base64_encode(hash_hmac('sha256', $customerReference, $this->apiSecret, true));
    }
}
