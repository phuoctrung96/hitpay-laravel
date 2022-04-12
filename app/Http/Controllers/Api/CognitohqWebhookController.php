<?php

namespace App\Http\Controllers\Api;

use App\Business;
use App\Enumerations\VerificationProvider;
use App\Http\Controllers\Controller;
use Exception;
use HitPay\Verification\Cognito\FlowSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades;

class CognitohqWebhookController extends Controller
{
    /**
     * @throws Exception
     */
    public function __invoke(Request $request)
    {
        if (!Facades\App::environment('production')) {
            Facades\Log::info('cognito webhook coming..');
        }

        $this->validateRequest($request);

        if (!Facades\App::environment('production')) {
            Facades\Log::info('all validated data good');
        }

        $postData = $request->post();

        if (!array_key_exists('data', $postData)) {
            Facades\Log::critical('Cognito data without key `data` on response ' . json_encode($postData));
            return null;
        }

        $eventData = $postData['data'];

//        if ($postData['event'] == 'flow_session.status.updated') {
//            $flowSessionId = $eventData['id'];
//            $businessId = $eventData['customer_reference'];
//
//            $business = Business::find($businessId);
//
//            if ($business == null) {
//                $this->setError("Business not found from cognito webhook with business ID {$businessId}");
//            }
//
//            if ($eventData['status'] === 'success') {
//                try {
//                    $flowSession = new FlowSession\Retrieve();
//                    $responseDecoded = $flowSession->getFlowSessionById($flowSessionId);
//                } catch (Exception $exception) {
//                    $this->setError("Failed when get flow session with id {$flowSessionId} : " );
//                }
//
//                if ($responseDecoded === null) {
//                    // $responseDecoded skipped because setError always exit the process
//                    $this->setError("Failed when get flow session with id {$flowSessionId} : " );
//                }
//
//                if ($responseDecoded['status'] == 'success') {
//                    $verification = $business->verifications()->latest()->first();
//
//                    if ($verification) {
//                        $verification->update([
//                            'identification' => $responseDecoded['user']['id_number']['value'],
//                            'name' => $responseDecoded['user']['name']['first'] . ' ' . $responseDecoded['user']['name']['last'],
//                            'status' => '',
//                            'cognitohq_data' => $responseDecoded,
//                            'verification_provider' => VerificationProvider::COGNITO,
//                            'verification_provider_account_id' => $flowSessionId,
//                            'verification_provider_status' => $responseDecoded['status'],
//                        ]);
//                    } else {
//                        // create new one
//                        $business->verifications()->create([
//                            'type' => $business->business_type == 'company' ? 'business' : 'personal',
//                            'identification' => $responseDecoded['user']['id_number']['value'],
//                            'name' => $responseDecoded['user']['name']['first'] . ' ' . $responseDecoded['user']['name']['last'],
//                            'status' => '',
//                            'cognitohq_data' => $responseDecoded,
//                            'verification_provider' => VerificationProvider::COGNITO,
//                            'verification_provider_account_id' => $flowSessionId,
//                            'verification_provider_status' => $responseDecoded['status'],
//                        ]);
//                    }
//
//                    if (
//                        isset($responseDecoded['user']['name']['first']) &&
//                        isset($responseDecoded['user']['name']['last'])
//                    ) {
//                        // updating first name, last name and display name
//                        $owner = $business->owner()->first();
//                        $owner->first_name = $responseDecoded['user']['name']['first'];
//                        $owner->last_name = $responseDecoded['user']['name']['last'];
//                        $owner->display_name = $owner->first_name . ' ' . $owner->last_name;
//                        $owner->save();
//                    }
//                }
//            }
//
//            if ($eventData['status'] == 'expired') {
//                // https://cognitohq.com/docs/reference#get_flow_session-status
//                // expired - The Flow session was active for more than 48 hours without being completed
//                // and was automatically marked as expired.
//                try {
//                    $flowRetry = new FlowSession\Retry();
//                    $flowRetry->setBusiness($business)->handle();
//                } catch (Exception $exception) {
//                    $this->setError("Failed when trying retry flow session with business id {$businessId} error: " . $exception->getMessage());
//                }
//            }
//        } else {
//            if (!Facades\App::environment('production')) {
//                Facades\Log::info('flow_session status '.$postData['event'].' come...');
//            }
//        }

        // Cognito need to return null
        // can't return with Response::json([],200)
        // https://cognitohq.com/docs/flow/webhooks#responding-to-webhooks
        return null;
    }

    /**
     * @param Request $request
     * @return void
     * @throws Exception
     */
    private function validateRequest(Request $request) : void
    {
        $authorization = $request->header('authorization', null);
        $authorizationArray = explode(",", $authorization);

        if (count($authorizationArray) !== 4) {
            $this->setError('Authorization header of cognito hq on webhook invalid');
        }

        // https://cognitohq.com/docs/flow/webhooks#verifying-webhook-signatures
        // https://playground.cognitohq.com/flow/templates/flwtmp_3cJySprWfkL4h9/settings/webhooks/whksub_cGkUCfoeTFBoRf/test

        $authSignatureKey = explode('="', $authorizationArray[0]);
        $requestSignatureKey = substr($authSignatureKey[1],0,-1);

        if ($requestSignatureKey !== Facades\Config::get('services.cognito.key')) {
            if (!Facades\App::environment('production')) {
                Facades\Log::info(print_r('authSignatureKey',true));
                Facades\Log::info(print_r($authSignatureKey,true));

                Facades\Log::info(print_r('requestSignatureKey',true));
                Facades\Log::info(print_r($requestSignatureKey,true));

                Facades\Log::info(print_r('cognito key',true));
                Facades\Log::info(print_r(Facades\Config::get('services.cognito.key'),true));
            }

            $this->setError('Authorization signature key cognito on webhook invalid');
        }

        $content = $request->getContent();

        $digestHeader = 'SHA-256=' . base64_encode(openssl_digest($content, 'sha256', true));

        $date = $request->header('date', null);

        $digest = $request->header('digest', null);

        $uri = $request->getRequestUri();

        $signingString = "(request-target): post ". $uri . PHP_EOL . "date: " . $date . PHP_EOL . "digest: " . $digestHeader;

        $signature = base64_encode(hash_hmac('sha256', $signingString, Facades\Config::get('services.cognito.secret'), true));

        $expectedHeader = 'Signature keyId="' .
            Facades\Config::get('services.cognito.key') .
            '",algorithm="hmac-sha256",headers="(request-target) date digest",signature="'.
            $signature . '"';

        if ($expectedHeader != $authorization) {
            if (!Facades\App::environment('production')) {
                Facades\Log::info(print_r('content',true));
                Facades\Log::info(print_r($content,true));

                Facades\Log::info(print_r('digestHeader',true));
                Facades\Log::info(print_r($digestHeader,true));

                Facades\Log::info(print_r('date',true));
                Facades\Log::info(print_r($date,true));

                Facades\Log::info(print_r('digest',true));
                Facades\Log::info(print_r($digest,true));

                Facades\Log::info(print_r('uri',true));
                Facades\Log::info(print_r($uri,true));

                Facades\Log::info(print_r('signingString',true));
                Facades\Log::info(print_r($signingString,true));

                Facades\Log::info(print_r('signature',true));
                Facades\Log::info(print_r($signature,true));

                Facades\Log::info(print_r('expectedHeader',true));
                Facades\Log::info(print_r($expectedHeader,true));

                Facades\Log::info(print_r('authorization',true));
                Facades\Log::info(print_r($authorization,true));
            }

            $this->setError('Expected Header Authorization signature key cognito on webhook invalid');
        }

        $requestTime = strtotime($date);
        $serverTime = time();
        $difference = abs($requestTime - $serverTime);

        if ($difference >= (15 * 60)) {
            $this->setError('Request time header authorization cognito on webhook invalid');
        }
    }

    private function setError($message)
    {
        Facades\Log::critical('set Error: ' . $message);
        throw new \Symfony\Component\HttpKernel\Exception\HttpException(401);
    }
}
