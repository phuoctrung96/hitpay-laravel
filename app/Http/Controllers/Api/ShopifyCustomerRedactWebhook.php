<?php

namespace App\Http\Controllers\Api;

use App\Business;
use App\BusinessShopifyStore;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ShopifyCustomerRedactWebhook extends Controller
{
    /**
     * @throws \Exception
     */
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        // https://shopify.dev/apps/webhooks/configuration/mandatory-webhooks#customers-redact
        $shopDomain = $request->get('shop_domain');

        if ($shopDomain === null) {
            throw new \Exception('Webhook from ShopifyCustomerRedactWebhook: Shop domain is empty from shopify request!');
        }

        $customer = $request->get('customer');

        if ($customer === null) {
            throw new \Exception('Webhook from ShopifyCustomerRedactWebhook: customer empty from shopify request!');
        }

        $customerEmail = $customer['email'];

        if ($customerEmail === null OR $customerEmail == "") {
            throw new \Exception('Webhook from ShopifyCustomerRedactWebhook: customer email empty from shopify request!');
        }

        $orderToRedacts = $request->get('orders_to_redact');

        if ($orderToRedacts === null) {
            throw new \Exception('Webhook from ShopifyCustomerRedactWebhook: orders_to_redact empty from shopify request!');
        }

        $shopifyStore = BusinessShopifyStore::where('shopify_domain', $shopDomain)->first();

        if (!$shopifyStore instanceof BusinessShopifyStore) {
            throw new \Exception("Webhook from ShopifyCustomerRedactWebhook checked that not have shop with domain $shopDomain");
        }

        $business = $shopifyStore->business;

        if (!$business instanceof Business) {
            throw new \Exception("Webhook from ShopifyCustomerRedactWebhook checked that not have shop with domain $shopDomain relate with hit-pay business");
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
}
