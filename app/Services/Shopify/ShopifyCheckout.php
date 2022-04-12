<?php
declare(strict_types=1);

namespace App\Services\Shopify;

use App\Business;
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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Class ShopifyCheckout
 * @package App\Services\Shopify
 */
class ShopifyCheckout
{
    /**
     * @throws Exception
     */
    public function createPaymentRequest(Request $request): array
    {
        if (!App::environment('production')) {
            Log::info(print_r($request->post(),true));
        }

        $apiKey = $request->get('api_key');

        $this->saveOrUpdateShopifyPayment($request);

        $baseUrl = 'https://' . config('app.subdomains.api').'/v1/payment-requests';

        if (Config::get('services.shopify.is_possible_test_mode')) {
            $isTestMode = $request->get('test');
        } else {
            // to testing like real payment on sandbox
            $isTestMode = false;
        }

        if (App::environment('local')) {
            $baseUrl = 'http://' . config('app.subdomains.api').'/v1/payment-requests';
        }

        if (Config::get('services.shopify.business_test_sandbox_to_staging')) {
            if ($isTestMode && App::environment('sandbox')) {
                $baseUrl = "https://api.staging.hit-pay.com/v1/payment-requests";
            }
        } else {
            if ($isTestMode && App::environment('production')) {
                $baseUrl = "https://api.sandbox.hit-pay.com/v1/payment-requests";
            }
        }

        $client = new Client([
            'headers' => [
                'X-BUSINESS-API-KEY' => $apiKey,
                'X-Requested-With' => 'XMLHttpRequest',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'verify' => false,
        ]);

        $redirectUrl = route('web.shopify.checkout.redirect', [
            'invoice' => $request->get('id'),
            'business_id' => $request->get('business_id'),
        ]);

        if (Config::get('services.shopify.business_test_sandbox_to_staging')) {
            if ($request->get('is_production_to_sandbox') && App::environment('staging')) {
                $redirectUrl = "https://staging.hit-pay.com/shopify/checkout/redirect?invoice={$request->get('id')}&business_id={$request->get('business_id')}";
            }
        } else {
            if ($request->get('is_production_to_sandbox') && App::environment('sandbox')) {
                $redirectUrl = "https://sandbox.hit-pay.com/shopify/checkout/redirect?invoice={$request->get('id')}&business_id={$request->get('business_id')}";
            }
        }

        $customer = $request->get('customer');

        $customerEmail = $customer['email'];
        $customerName = $customer['billing_address']['given_name'] ?? '';

        $webhookUrl = route('securecheckout.shopify.webhook', [
            'invoice' => $request->get('id')
        ]);

        if (Config::get('services.shopify.business_test_sandbox_to_staging')) {
            if ($request->get('is_production_to_sandbox') && App::environment('staging')) {
                $webhookUrl = "https://securecheckout.staging.hit-pay.com/shopify/webhook?invoice=" . $request->get('id');
            }
        } else {
            if ($request->get('is_production_to_sandbox') && App::environment('sandbox')) {
                $webhookUrl = "https://securecheckout.sandbox.hit-pay.com/shopify/webhook?invoice=" . $request->get('id');
            }
        }

        $paramsData = [
            'amount' => $request->get('amount'),
            'currency' => $request->get('currency'),
            'name' => $customerName,
            'email' => $customerEmail,
            'reference_number' => $request->get('id'),
            'redirect_url' => $redirectUrl,
            'send_email' => 'true',
            'webhook' => $webhookUrl,
            'channel' => PluginProvider::APISHOPIFY,
        ];

        $response = $client->post($baseUrl, [
            'form_params' => $paramsData
        ]);

        $response = json_decode((string) $response->getBody(), true);

        return $response;
    }

    /**
     * @param Request $request
     * @return void
     */
    private function saveOrUpdateShopifyPayment(Request $request): void
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
        $savedRequest['shopify_token_real'] = $request->get('shopify_token_real');
        $savedRequest['shopify_api_version'] = $request->get('shopify_api_version');
        $savedRequest['shopify_request_id'] = $request->get('shopify_request_id');
        $savedRequest['business_id'] = $request->get('business_id');
        $savedRequest['shopify_domain'] = $request->get('shopify_domain');
        $savedRequest['shopify_domain_real'] = $request->get('shopify_domain_real');
        $savedRequest['test'] = $request->get('test');

        $businessShopifyPayment = BusinessShopifyPayment::where('invoice_id', $savedRequest['id'])
            ->where('business_id', $savedRequest['business_id'])
            ->first();

        if (!$businessShopifyPayment) {
            $businessShopifyPayment = new BusinessShopifyPayment();
            $businessShopifyPayment->invoice_id = $savedRequest['id'];
            $businessShopifyPayment->business_id = $savedRequest['business_id'];
            $businessShopifyPayment->email = $savedRequest['customer']['email'];
        }

        $businessShopifyPayment->gid = $savedRequest['gid'];
        $businessShopifyPayment->request_id = $savedRequest['shopify_request_id'];
        $businessShopifyPayment->request_data = $savedRequest;
        $businessShopifyPayment->save();
    }

    /**
     * @param $request
     * @return bool
     * @throws ShopifyCheckoutException
     * @throws Exception
     */
    public function listenWebhook($request): bool
    {
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
    }

    /**
     * @param Request $request
     * @param PaymentRequest $charge
     * @return void
     */
    private function validateRequest(Request $request, PaymentRequest $charge): void
    {
        if (!$request->has('hmac')) {
            Log::critical('shopify validateRequest invalid hmac');

            App::abort(404);
        }

        if(!$request->has('invoice')) {
            Log::critical('shopify validateRequest invalid invoice not set!');

            App::abort(404);
        }

        if($request->get('status') != 'completed') {
            Log::critical('shopify validateRequest status not completed');

            App::abort(404);
        }

        $isValidHmac = false;

        foreach ($charge->business->apiKeys()->where('is_enabled', 1)->get() as $apiKey) {
            if(hash_equals($request->input('hmac'), $this->makeHmacFromRequest($request, (string) $apiKey->salt))) {
                $isValidHmac = true;
            }
        }

        if(!$isValidHmac) {
            Log::critical('shopify validateRequest invalid hmac with request + api salt');

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

    /**
     * @throws Exception
     */
    public function createPaymentRequestToSandbox(Request $request)
    {
        $this->saveOrUpdateShopifyPayment($request);

        $client = new Client();

        $url = route('securecheckout.shopify.payment.charge');

        if (Config::get('services.shopify.business_test_sandbox_to_staging')) {
            if (App::environment('sandbox')) { // test on sandbox go to staging
                $url = "https://securecheckout.staging.hit-pay.com/shopify/charge";
            }
        } else {
            if (App::environment('production')) { // test on production go to sandbox
                $url = "https://securecheckout.sandbox.hit-pay.com/shopify/charge";
            }
        }

        $body = $request->all();
        $body['is_production_to_sandbox'] = true;
        $body['shopify_token_real'] = $request->get('shopify_token_real');

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
