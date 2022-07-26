<?php

namespace App\Http\Controllers\Api;

use App\Business;
use App\BusinessShopifyStore;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ShopifyCustomerDataRequestWebhook extends Controller
{
    /**
     * @throws \Exception
     */
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        // https://shopify.dev/apps/webhooks/configuration/mandatory-webhooks#customers-data_request
        $shopDomain = $request->get('shop_domain');

        if ($shopDomain === null) {
            throw new \Exception('Webhook from ShopifyCustomerDataRequestWebhook: Shop domain is empty from shopify request!');
        }

        $customer = $request->get('customer');

        if ($customer === null) {
            throw new \Exception('Webhook from ShopifyCustomerDataRequestWebhook: customer empty from shopify request!');
        }

        $customerEmail = $customer['email'];

        if ($customerEmail === null OR $customerEmail == "") {
            throw new \Exception('Webhook from ShopifyCustomerDataRequestWebhook: customer email empty from shopify request!');
        }

        $orderRequests = $request->get('orders_requested');

        if ($orderRequests === null) {
            throw new \Exception('Webhook from ShopifyCustomerDataRequestWebhook: orders_requested empty from shopify request!');
        }

        $shopifyStore = BusinessShopifyStore::where('shopify_domain', $shopDomain)->first();

        if (!$shopifyStore instanceof BusinessShopifyStore) {
            throw new \Exception("Webhook from ShopifyCustomerDataRequestWebhook checked that not have shop with domain $shopDomain");
        }

        $business = $shopifyStore->business;

        if (!$business instanceof Business) {
            throw new \Exception("Webhook from ShopifyCustomerDataRequestWebhook checked that not have shop with domain $shopDomain relate with hit-pay business");
        }

        $businessShopifyPayments = $business->shopifyPayments()->where('email', $customerEmail)
            ->whereIn('invoice_id', $orderRequests)
            ->get();

        if ($businessShopifyPayments->count() > 0) {
            $businessShopifyPayments->transform(function($item) {
                return [
                    'invoice_id' => $item->invoice_id,
                    'customer_name' => $item->email,
                    'customer_email' => $item->request_data['customer']['billing_address']['given_name'],
                    'amount' => $item->request_data['amount']
                ];
            });
        }

        return Response::json($businessShopifyPayments);
    }
}
