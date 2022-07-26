<?php

namespace App\Http\Controllers\Api;

use App\BusinessShopifyStore;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ShopifyUninstallAppWebhook extends Controller
{
    /**
     * @throws \Exception
     */
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        $shopDomain = $request->get('shop_domain');

        if ($shopDomain === null) {
            throw new \Exception('Webhook from ShopifyUninstallAppWebhook: Shop domain is empty from shopify request!');
        }

        $shopifyStore = BusinessShopifyStore::where('shopify_domain', $shopDomain)->first();

        if ($shopifyStore) {
            $shopifyStore->delete(); // soft delete
        }

        return Response::json();
    }
}
