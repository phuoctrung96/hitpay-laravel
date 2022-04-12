<?php
declare(strict_types=1);

namespace App\Services\Shopify;

use App\Business;
use App\BusinessShopifyPayment;
use App\BusinessShopifyRefund;
use App\Jobs\Providers\Shopify\RefundSessionRejectJob;
use App\Jobs\Providers\Shopify\RefundSessionResolveJob;
use App\Services\Shopify\Api\Exceptions\DuplicateRefundException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Class ShopifyRefund
 * @package App\Services\Shopify
 */
class ShopifyRefund
{
    /**
     * @param Request $request
     * @return array
     * @throws DuplicateRefundException
     */
    public function createFromRequest(Request $request): array
    {
        try {
            if (!App::environment('production')) {
                Log::info('createFromRequest:');
                Log::info(print_r($request->post(),true));
            }

            $apiKey = $request->get('api_key');

            $shopifyRefundId = $request->get('payment_id');

            $shopifyPaymentId = $request->get('payment_id');

            $shopifyAmount = $request->get('amount');

            $business = $request->get('business');

            if (!$business instanceof Business) {
                throw new \Exception("Business not found. Please check the ShopifyAccessMiddleware key `business`");
            }

            $businessShopifyPayment = $business->shopifyPayments()->where('invoice_id', $shopifyPaymentId)->first();

            if (!$businessShopifyPayment instanceof BusinessShopifyPayment) {
                throw new \Exception("BusinessShopifyPayment not found.
                    From invoice id {$shopifyPaymentId}");
            }

            $shopifyPaymentData = $businessShopifyPayment->request_data;

            if (isset($shopifyPaymentData['test']) && $shopifyPaymentData['test'] === true) {
                Log::info('test payment data come');
                $isProductionToSandbox = $request->get('is_production_to_sandbox');

                if ($isProductionToSandbox) {
                    Log::info('isProductionToSandbox come');
                    $businessShopifyRefund = $business->shopifyRefunds()->where('payment_id', $shopifyPaymentId)
                        ->where('refund_id', $shopifyRefundId)->first();

                    if (!$businessShopifyRefund instanceof BusinessShopifyRefund) {
                        Log::info('create new refund data');
                        $business->shopifyRefunds()->create([
                            'payment_id' => $shopifyPaymentId,
                            'refund_id' => $shopifyRefundId,
                            'gid' => $request->get('gid'),
                            'request_data' => $request->all()
                        ]);
                    } else {
                        Log::info('become duplicate');
                        // duplicate, throw exception
                        throw new DuplicateRefundException("Duplicate shopify refund with
                            Business ID {$business->getKey()} Payment ID {$shopifyPaymentId} and Refund ID {$shopifyRefundId}");
                    }
                } else {
                    Log::info('request to sandbox A12');
                    try {
                        return $this->createFromRequestToSandbox($request);
                    } catch (\Exception $exception) {
                        Log::info('request to sandbox failed with message' . $exception->getMessage());
                        // possible duplicate refund error come to handle loop
                        return [];
                    }
                }
            }

            $paymentRequest = $business->paymentRequests()->where('reference_number', $shopifyPaymentId)->first();

            if (!$paymentRequest) {
                throw new \Exception("Payment not found with reference_number {$shopifyPaymentId} with business
                    ID {$business->getKey()}", 404);
            }

            $payments = $paymentRequest->getPayments();

            if (count($payments) <= 0) {
                throw new \Exception("Payment charge success not found");
            }

            $payment = $payments->shift();

            $baseUrl = 'https://' . config('app.subdomains.api').'/v1/refund';

            if (App::environment('local')) {
                $baseUrl = 'http://' . config('app.subdomains.api').'/v1/refund';
            }

            $client = new Client([
                'headers' => [
                    'X-BUSINESS-API-KEY' => $apiKey,
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'verify' => false,
            ]);

            $paramsData = [
                'amount' => $shopifyAmount,
                'payment_id' => $payment->getKey()
            ];

            try {
                Log::info('trying to send refund request');
                $response = $client->post($baseUrl, [
                    'form_params' => $paramsData
                ]);

                $refundRequest = json_decode((string) $response->getBody(), true);
            } catch (\Exception $exception) {
                Log::info('got exception when trying refund request');
                $businessShopifyRefund = $business->shopifyRefunds()->where('payment_id', $shopifyPaymentId)
                    ->where('refund_id', $shopifyRefundId)->first();

                if ($businessShopifyRefund instanceof BusinessShopifyRefund) {
                    Log::info('delete business shopify refund');
                    $businessShopifyRefund->delete();
                }

                Log::info('throw exception');

                throw $exception;
            }

            if ($request->get('shopify_token_real') === $request->get('shopify_token')) {
                $shopifyToken = $request->get('shopify_token');
            } else {
                if (Config::get('services.shopify.is_possible_test_mode')) {
                    $isTestMode = $request->get('test');
                } else {
                    // to testing like real payment on sandbox
                    $isTestMode = false;
                }

                if (Config::get('services.shopify.business_test_sandbox_to_staging')) {
                    if ($isTestMode && App::environment('sandbox')) {
                        $shopifyToken = $request->get('shopify_token');
                    } else {
                        $shopifyToken = $request->get('shopify_token_real');
                    }
                } else {
                    if ($isTestMode && App::environment('production')) {
                        $shopifyToken = $request->get('shopify_token');
                    } else {
                        $shopifyToken = $request->get('shopify_token_real');
                    }
                }
            }

            RefundSessionResolveJob::dispatch([
                'business_id' => $business->getKey(),
                'payment_id' => $shopifyPaymentId,
                'refund_id' => $shopifyRefundId,
                'shopifyToken' => $shopifyToken,
                'shopifyDomain' => $request->get('shopify_domain_real'),
                'shopifyApiVersion' => $request->get('shopify_api_version'),
                'shopifyGid' => $request->get('gid')
            ]);

            return $refundRequest;
        } catch (ServerException $exception) {
            Log::critical("server exception from shopify refund with message: {$exception->getMessage()} \n
                {$exception->getTraceAsString()}");

            // send exception object
            $this->refundRejectSession($request, $exception);

            return [];
        } catch (ClientException $exception) {
            Log::critical("client exception from shopify refund with message: {$exception->getMessage()} \n
                {$exception->getTraceAsString()}");

            // send exception object
            $this->refundRejectSession($request, $exception);

            return [];
        } catch (DuplicateRefundException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            Log::critical("exception from shopify refund with message: {$exception->getMessage()} \n
                {$exception->getTraceAsString()}");
            // only send message
            $this->refundRejectSession($request, $exception->getMessage());

            return [];
        }
    }

    /**
     * @param $request
     * @param $exception
     * @return void
     */
    private function refundRejectSession($request, $exception)
    {
        if (is_object($exception)) {
            $exceptionDecoded = json_decode($exception->getResponse()->getBody()->__toString(),true);
            $reasonString = $exceptionDecoded['message'];
        } else {
            $reasonString = $exception;
        }

        $reasonMessage = $reasonString;

        $reason = [
            "code" => "PROCESSING_ERROR",
            "merchantMessage" => $reasonMessage
        ];

        if ($request->get('shopify_token_real') === $request->get('shopify_token')) {
            $shopifyToken = $request->get('shopify_token');
        } else {
            if (Config::get('services.shopify.is_possible_test_mode')) {
                $isTestMode = $request->get('test');
            } else {
                // to testing like real payment on sandbox
                $isTestMode = false;
            }

            if (Config::get('services.shopify.business_test_sandbox_to_staging')) {
                if ($isTestMode && App::environment('sandbox')) {
                    $shopifyToken = $request->get('shopify_token_real');
                } else {
                    $shopifyToken = $request->get('shopify_token');
                }
            } else {
                if ($isTestMode && App::environment('production')) {
                    $shopifyToken = $request->get('shopify_token_real');
                } else {
                    $shopifyToken = $request->get('shopify_token');
                }
            }
        }

        RefundSessionRejectJob::dispatch([
            'business_id' => $request->get('business_id'),
            'payment_id' => $request->get('payment_id'),
            'refund_id' => $request->get('id'),
            'shopifyToken' => $shopifyToken,
            'shopifyDomain' => $request->get('shopify_domain'),
            'shopifyApiVersion' => $request->get('shopify_api_version'),
            'shopifyGid' => $request->get('gid'),
            'shopifyReason' => $reason
        ]);
    }

    /**
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function createFromRequestToSandbox(Request $request): array
    {
        $client = new Client();

        $url = route('securecheckout.shopify.payment.refund');

        if (Config::get('services.shopify.business_test_sandbox_to_staging')) {
            if (App::environment('sandbox')) { // test on sandbox go to staging
                $url = "https://securecheckout.staging.hit-pay.com/shopify/refund";
            }
        } else {
            if (App::environment('production')) { // test on production go to sandbox
                $url = "https://securecheckout.sandbox.hit-pay.com/shopify/refund";
            }
        }

        $body = $request->all();
        $body['is_production_to_sandbox'] = true;
        $body['shopify_token_real'] = $request->get('shopify_token_real');
        $body['shopify_domain_real'] = $request->get('shopify_domain_real');

        $response = $client->post($url, [
            'body' => json_encode($body),
            'headers' => [
                'Content-Type' => 'application/json',
                'Shopify-Shop-Domain' => $request->header('Shopify-Shop-Domain'),
                'Shopify-Request-Id' => $request->header('Shopify-Request-Id'),
                'Shopify-Api-Version' => $request->header('Shopify-Api-Version'),
            ],
            'verify' => false
        ]);

        $responseArray = $response->getBody()->getContents();

        return json_decode((string) $responseArray, true);
    }
}
