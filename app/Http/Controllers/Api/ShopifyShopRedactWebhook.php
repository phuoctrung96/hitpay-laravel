<?php

namespace App\Http\Controllers\Api;

use App\BusinessShopifyStore;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ShopifyShopRedactWebhook extends Controller
{
    /**
     * @throws \Exception
     */
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        // https://shopify.dev/apps/webhooks/configuration/mandatory-webhooks#shop-redact
        // 48 hours after a store owner uninstalls your app, Shopify sends a payload on the shop/redact topic.
        // This webhook provides the store's shop_id and shop_domain so that you can erase data for that store
        // from your database.
        $shopDomain = $request->get('shop_domain');

        if ($shopDomain === null) {
            throw new \Exception('Webhook from ShopifyShopRedactWebhook: Shop domain is empty from shopify request!');
        }

        $shopifyStore = BusinessShopifyStore::where('shopify_domain', $shopDomain)->first();

        if ($shopifyStore) {
            $shopifyStore->delete(); // soft delete
        }

        return Response::json();
    }
}
