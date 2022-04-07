<?php

namespace App\Http\Controllers\Api;

use App\Business;
use App\BusinessShopifyStore;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class ShopifyCustomerRedactWebhook extends Controller
{
    /**
     * @throws \Exception
     */
    public function __invoke(Request $request)
    {
        if (!App::environment('production')) {
            Log::info('request come');
            Log::info(json_encode($request->all()));
        }

        // https://shopify.dev/apps/webhooks/configuration/https#step-5-verify-the-webhook
        $hmacHeader = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] ?? ''; // for test on local set empty string;
        $data = file_get_contents('php://input');

        if (!$this->verifyWebhook($data, $hmacHeader)) {
            Log::critical("Webhook from ShopifyCustomerRedactWebhook not verified
                with \n data: {$data} \n hmacHeader: {$hmacHeader}");

            throw new \Symfony\Component\HttpKernel\Exception\HttpException(401);
        }

        // https://shopify.dev/apps/webhooks/configuration/mandatory-webhooks#customers-redact
        $shopDomain = $request->get('shop_domain');

        if ($shopDomain === null) {
            throw new \Exception('Webhook from ShopifyCustomerRedactWebhook: Shop domain is
                empty from shopify request!');
        }

        $customer = $request->get('customer');

        if ($customer === null) {
            throw new \Exception('Webhook from ShopifyCustomerRedactWebhook:
                customer empty from shopify request!');
        }

        $customerEmail = $customer['email'];

        if ($customerEmail === null OR $customerEmail == "") {
            throw new \Exception('Webhook from ShopifyCustomerRedactWebhook:
                customer email empty from shopify request!');
        }

        $orderToRedacts = $request->get('orders_to_redact');

        if ($orderToRedacts === null) {
            throw new \Exception('Webhook from ShopifyCustomerRedactWebhook:
                orders_to_redact empty from shopify request!');
        }

        $shopifyStore = BusinessShopifyStore::where('shopify_domain', $shopDomain)->first();

        if (!$shopifyStore instanceof BusinessShopifyStore) {
            throw new \Exception("Webhook from ShopifyCustomerRedactWebhook checked
                that not have shop with domain {$shopDomain}");
        }

        $business = $shopifyStore->business;

        if (!$business instanceof Business) {
            throw new \Exception("Webhook from ShopifyCustomerRedactWebhook checked
                that not have shop with domain {$shopDomain} relate with hit-pay business");
        }

        $businessShopifyPayments = $business->shopifyPayments()->where('email', $customerEmail)
            ->whereIn('invoice_id', $orderToRedacts)
            ->get();

        $returnedData = [];

        if ($businessShopifyPayments->count() > 0) {
            $returnedData = $businessShopifyPayments->map(function($item) {
                return [
                    'invoice_id' => $item->invoice_id,
                    'customer_name' => $item->email,
                    'customer_email' => $item->request_data['customer']['billing_address']['given_name'],
                    'amount' => $item->request_data['amount']
                ];
            });
        }

        foreach ($businessShopifyPayments as $order) {
            $order->delete(); // soft delete
        }

        return Response::json($returnedData);
    }

    /**
     * @param string $data
     * @param string $hmacHeader
     * @return bool
     */
    private function verifyWebhook(string $data, string $hmacHeader): bool
    {
        // https://shopify.dev/apps/webhooks/configuration/https#step-5-verify-the-webhook

        if (App::environment('local')) {
            return true;
        }

        $calculatedHmac = base64_encode(
            hash_hmac('sha256', $data, Config::get('service.shopify.client_secret_v2'), true)
        );

        return hash_equals($hmacHeader, $calculatedHmac);
    }
}