<?php
declare(strict_types=1);

namespace App\Services\Shopify;

use App\Business\PaymentRequest;
use App\Jobs\Providers\Shopify\RefundSessionRejectJob;
use App\Jobs\Providers\Shopify\RefundSessionResolveJob;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
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
     */
    public function createFromRequest(Request $request): array
    {
        try {
            $apiKey = $request->get('api_key');

            $shopifyPaymentId = $request->get('payment_id');

            $shopifyAmount = $request->get('amount');

            $paymentRequest = PaymentRequest::where('reference_number', $shopifyPaymentId)
                ->first();

            if (!$paymentRequest) {
                throw new \Exception("Payment not found", 404);
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

            $response = $client->post($baseUrl, [
                'form_params' => $paramsData
            ]);

            $paymentRequest = json_decode((string) $response->getBody(), true);

            RefundSessionResolveJob::dispatch([
                'shopifyToken' => $request->get('shopify_token'),
                'shopifyDomain' => $request->get('shopify_domain'),
                'shopifyApiVersion' => $request->get('shopify_api_version'),
                'shopifyGid' => $request->get('gid')
            ]);

            return $paymentRequest;
        } catch (ServerException $exception) {
            Log::channel('shopify')->error($exception);

            // send exception object
            $this->refundRejectSession($request, $exception);

            return [];
        } catch (ClientException $exception) {
            // send exception object
            $this->refundRejectSession($request, $exception);

            return [];
        } catch (\Exception $exception) {
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

        RefundSessionRejectJob::dispatch([
            'shopifyToken' => $request->get('shopify_token'),
            'shopifyDomain' => $request->get('shopify_domain'),
            'shopifyApiVersion' => $request->get('shopify_api_version'),
            'shopifyGid' => $request->get('gid'),
            'shopifyReason' => $reason
        ]);
    }
}
