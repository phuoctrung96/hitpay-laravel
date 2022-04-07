<?php

namespace App\Http\Controllers\Api;

use App\BusinessShopifyStore;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class ShopifyShopRedactWebhook extends Controller
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
            Log::critical("Webhook from ShopifyShopRedactWebhook not verified
                with \n data: {$data} \n hmacHeader: {$hmacHeader}");

            throw new \Symfony\Component\HttpKernel\Exception\HttpException(401);
        }

        // https://shopify.dev/apps/webhooks/configuration/mandatory-webhooks#shop-redact
        // 48 hours after a store owner uninstalls your app, Shopify sends a payload on the shop/redact topic.
        // This webhook provides the store's shop_id and shop_domain so that you can erase data for that store
        // from your database.
        $shopDomain = $request->get('shop_domain');

        if ($shopDomain === null) {
            throw new \Exception('Webhook from ShopifyShopRedactWebhook: Shop domain is
                empty from shopify request!');
        }

        $shopifyStore = BusinessShopifyStore::where('shopify_domain', $shopDomain)->first();

        if ($shopifyStore) {
            $shopifyStore->delete(); // soft delete
        }

        return Response::json();
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
