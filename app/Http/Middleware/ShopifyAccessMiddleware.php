<?php

namespace App\Http\Middleware;

use App\BusinessShopifyStore;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class ShopifyAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$this->isValidShopifySignature($request)) {
            App::abort(401, 'Invalid signature.');
        }

        return $next($request);
    }

    private function isValidShopifySignature($request)
    {
        // get by shop domain
        $shopifyShopDomain = $request->header('shopify-shop-domain');
        $shopifyRequestId = $request->header('shopify-request-id');
        $shopifyApiVersion = $request->header('shopify-api-version');

        if ($shopifyApiVersion == "" || $shopifyRequestId == "" || $shopifyShopDomain == "") {
            Log::critical('[CHECKOUT-SHOPIFY-V2]: empty data header');

            App::abort(404, 'Invalid');
        }

        // get shop by shopify-shop-domain
        $businessShopifyStore = BusinessShopifyStore::where('shopify_domain', $shopifyShopDomain)->first();

        if (!$businessShopifyStore) {
            Log::critical(sprintf('[CHECKOUT SHOPIFY V2]: shopify_domain found with domain %s and request id %s', $shopifyShopDomain, $shopifyRequestId));

            App::abort(404, 'Invalid client.');
        }

        $business = $businessShopifyStore->business()->first();

        $businessApiKey = $business->apiKeys->first();

        if (!$businessApiKey) {
            Log::critical('[CHECKOUT-SHOPIFY-V2]: empty business api key');

            App::abort(404, 'Invalid');
        }

        $request->attributes->set('api_key'     , $businessApiKey->api_key);
        $request->attributes->set('business_id' , $business->getKey());
        $request->attributes->set('shopify_token' , $businessShopifyStore->shopify_token);
        $request->attributes->set('shopify_domain' , $businessShopifyStore->shopify_domain);
        $request->attributes->set('shopify_api_version' , $shopifyApiVersion);
        $request->attributes->set('shopify_request_id' , $shopifyRequestId);

        return true;
    }
}
