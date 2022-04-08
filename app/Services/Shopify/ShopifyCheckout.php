<?php
declare(strict_types=1);

namespace App\Services\Shopify;

use App\Business\Charge;
use App\Business\PaymentRequest;
use App\BusinessShopifyPayment;
use App\Enumerations\Business\PaymentRequestStatus;
use App\Enumerations\Business\PluginProvider;
use App\Exceptions\ShopifyCheckoutException;
use App\Manager\ChargeManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * Class ShopifyCheckout
 * @package App\Services\Shopify
 */
class ShopifyCheckout
{
    /**
     * @throws ShopifyCheckoutException
     */
    public function createPaymentRequest(Request $request): array
    {
        try {
            $apiKey = $request->get('api_key');

            $this->saveOrUpdateShopifyPayment($request);

            $baseUrl = 'https://' . config('app.subdomains.api').'/v1/payment-requests';

            if (App::environment('local')) {
                $baseUrl = 'http://' . config('app.subdomains.api').'/v1/payment-requests';
            }

            $client = new Client([
                'headers' => [
                    'X-BUSINESS-API-KEY' => $apiKey,
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'verify' => false,
            ]);

            $paymentMethod = $request->get('payment_method');

            if ($paymentMethod) {
                $redirectUrl = $paymentMethod['data']['cancel_url'];
            }

            $customer = $request->get('customer');

            $email = $customer['email'];

            $webhookUrl = route('securecheckout.shopify.webhook', [
                'invoice' => $request->get('id')
            ]);

            $paramsData = [
                'amount' => $request->get('amount'),
                'currency' => $request->get('currency'),
                'name' => 'Invoice #' . $request->get('id'),
                'email' => $email,
                'reference_number' => $request->get('id'),
                'redirect_url' => $redirectUrl,
                'send_email' => 'true',
                'webhook' => $webhookUrl,
                'channel' => PluginProvider::APISHOPIFY,
            ];

            $response = $client->post($baseUrl, [
                'form_params' => $paramsData
            ]);

            $paymentRequest = json_decode((string) $response->getBody(), true);

            return $paymentRequest;
        } catch (ServerException $exception) {
            Log::channel('shopify')->error($exception);
            throw new \Exception($exception->getResponse()->getBody()->__toString());
        } catch (Exception $exception) {
            throw new ShopifyCheckoutException($exception->getMessage());
        }
    }

    private function saveOrUpdateShopifyPayment($request)
    {
        $savedRequest['id'] = $request->get('id');
        $savedRequest['gid'] = $request->get('gid');
        $savedRequest['payment_method'] = $request->get('payment_method');
        $savedRequest['amount'] = $request->get('amount');
        $savedRequest['currency'] = $request->get('currency');
        $savedRequest['customer'] = $request->get('customer');
        $savedRequest['kind'] = $request->get('kind');
        $savedRequest['api_key'] = $request->get('api_key');
        $savedRequest['shopify_token'] = $request->get('shopify_token');
        $savedRequest['shopify_api_version'] = $request->get('shopify_api_version');
        $savedRequest['shopify_request_id'] = $request->get('shopify_request_id');
        $savedRequest['business_id'] = $request->get('business_id');
        $savedRequest['shopify_domain'] = $request->get('shopify_domain');

        $businessShopifyPayment = BusinessShopifyPayment::where('invoice_id', $savedRequest['id'])
            ->where('business_id', $savedRequest['business_id'])
            ->first();

        if (!$businessShopifyPayment) {
            $businessShopifyPayment = new BusinessShopifyPayment();
            $businessShopifyPayment->invoice_id = $savedRequest['id'];
            $businessShopifyPayment->business_id = $savedRequest['business_id'];
            $businessShopifyPayment->gid = $savedRequest['gid'];
            $businessShopifyPayment->request_id = $savedRequest['shopify_request_id'];
            $businessShopifyPayment->request_data = json_encode($savedRequest);
            $businessShopifyPayment->save();
        } else {
            $businessShopifyPayment->gid = $savedRequest['gid'];
            $businessShopifyPayment->request_id = $savedRequest['shopify_request_id'];
            $businessShopifyPayment->request_data = json_encode($savedRequest);
            $businessShopifyPayment->save();
        }

        return $businessShopifyPayment;
    }

    /**
     * @param $request
     * @return bool
     * @throws ShopifyCheckoutException
     * @throws Exception
     */
    public function listenWebhook($request)
    {
        try {
            $paymentRequest = PaymentRequest::findOrFail($request->input('payment_request_id'));

            $this->validateRequest($request, $paymentRequest);

            sleep(3);

            $maxAttempts = 5;

            $attempt = 1;

            $isInvoiceMarkedAsPaid = false;

            while($attempt <= $maxAttempts && !$isInvoiceMarkedAsPaid) {
                $payments = $paymentRequest->getPayments();

                if ($payments->count() > 0) {
                    if ($request->input('status') == PaymentRequestStatus::COMPLETED) {
                        /** @var Charge $payment */
                        $payment = $payments->shift();

                        $service = new ShopifySalesService($paymentRequest->business);

                        $isInvoiceMarkedAsPaid = $service->markInvoiceAsPaid(
                            $request->get('invoice')
                        );
                    }
                }

                $attempt++;

                sleep(2);
            }

            if (!$isInvoiceMarkedAsPaid) {
                return false;
            }

            return true;
        } catch (ServerException $exception) {
            Log::channel('shopify')->error($exception);
            throw new \Exception($exception->getResponse()->getBody()->__toString());
        } catch (Exception $exception) {
            throw new ShopifyCheckoutException($exception->getMessage());
        }
    }

    /**
     * @param Request $request
     * @param PaymentRequest $charge
     * @return void
     */
    private function validateRequest(Request $request, PaymentRequest $charge)
    {
        if (!$request->has('hmac')) {
            App::abort(404);
        }

        if(!$request->has('invoice')) {
            App::abort(404);
        }

        if($request->get('status') != 'completed') {
            App::abort(404);
        }

        $isValidHmac = false;

        foreach ($charge->business->apiKeys()->where('is_enabled', 1)->get() as $apiKey) {
            if(hash_equals($request->input('hmac'), $this->makeHmacFromRequest($request, (string) $apiKey->salt))) {
                $isValidHmac = true;
            }
        }

        if(!$isValidHmac) {
            App::abort(404);
        }
    }

    /**
     * @param Request $request
     * @param string $salt
     * @return string
     */
    private function makeHmacFromRequest(Request $request, string $salt): string
    {
        return resolve(ChargeManagerInterface::class)
            ->generateSignatureArray($salt, $request->only(
                'payment_id',
                'payment_request_id',
                'phone',
                'amount',
                'currency',
                'status',
                'reference_number'
            ));
    }
}
