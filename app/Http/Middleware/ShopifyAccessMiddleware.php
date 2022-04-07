<?php

namespace App\Http\Middleware;

use App\Business;
use App\BusinessShopifyStore;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ShopifyAccessMiddleware
{
    private string $shopifyShopDomain;
    private string $shopifyRequestId;
    private string $shopifyApiVersion;
    private bool $isTestMode;
    private bool $isProductionToSandbox;

    /**
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @return mixed
     * @throws \Exception
     */
    public function handle(\Illuminate\Http\Request $request, Closure $next)
    {
        if (!App::environment('production')) {
            Log::info('test request: ');
            Log::info(print_r($request->post(),true));
        }

        $this->shopifyShopDomain = $request->header('shopify-shop-domain');
        $this->shopifyRequestId = $request->header('shopify-request-id');
        $this->shopifyApiVersion = $request->header('shopify-api-version');

        // TODO set it false, on test mode shopify refund not set test params
        if (Config::get('services.shopify.is_possible_test_mode')) {
            $defaultTest = false;

            if (!App::environment('production')) {
                $defaultTest = true;
            }

            $this->isTestMode = $request->get('test', $defaultTest);
        } else {
            // to testing like real payment on sandbox
            $this->isTestMode = false;
        }

        $this->isProductionToSandbox = $request->get('is_production_to_sandbox', false);
        # $this->isProductionToSandbox = false; // set it always false when testing on sandbox without test mode

        if (!$this->isValidShopifySignature()) {
            Log::critical(sprintf('[CHECKOUT SHOPIFY V2]: invalid request shopify
                with domain %s and request id %s', $this->shopifyShopDomain, $this->shopifyRequestId));

            App::abort(401, 'Invalid signature.');
        }

        $request = $this->setProductionData($request);

        return $next($request);
    }

    /**
     * @return bool
     */
    private function isValidShopifySignature(): bool
    {
        if ($this->shopifyApiVersion == "" || $this->shopifyRequestId == "" || $this->shopifyShopDomain == "") {
            return false;
        }

        return true;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Request
     * @throws \Exception
     */
    private function setProductionData(\Illuminate\Http\Request $request): \Illuminate\Http\Request
    {
        if ($this->isProductionToSandbox) {
            // hardcode business account
            $business = Business::find(Config::get('services.shopify.business_id_test_v2'));

            if ($business === null) {
                throw new \Exception("Shopify test not yet set business id test mode");
            }

            $businessShopifyStore = BusinessShopifyStore::where('business_id', $business->getKey())->first();
        } else {
            // get shop by shopify-shop-domain
            $businessShopifyStore = BusinessShopifyStore::where('shopify_domain', $this->shopifyShopDomain)->first();
        }

        if (!$businessShopifyStore) {
            Log::critical(sprintf('[CHECKOUT SHOPIFY V2]: shopify_domain
                not found with domain %s and request id %s', $this->shopifyShopDomain, $this->shopifyRequestId));

            App::abort(404, 'Invalid client.');
        }

        $business = $businessShopifyStore->business()->first();

        $businessApiKey = $business->apiKeys->first();

        if (!$businessApiKey) {
            Log::critical('[CHECKOUT-SHOPIFY-V2]: empty business api key');

            App::abort(404, 'Invalid');
        }

        $request->attributes->set('api_key', $businessApiKey->api_key);
        $request->attributes->set('business_id', $business->getKey());
        $request->attributes->set('business', $business);
        $request->attributes->set('shopify_token', $businessShopifyStore->shopify_token);
        $request->attributes->set('shopify_domain', $businessShopifyStore->shopify_domain);
        $request->attributes->set('shopify_domain_real', $this->shopifyShopDomain);
        $request->attributes->set('shopify_api_version', $this->shopifyApiVersion);
        $request->attributes->set('shopify_request_id', $this->shopifyRequestId);

        if (Config::get('services.shopify.business_test_sandbox_to_staging')) {
            if ($this->isTestMode && App::environment('sandbox')) {
                $request->attributes->set('is_production_to_sandbox', true);

                $request->attributes->set('shopify_token_real', $businessShopifyStore->shopify_token);
            }
        } else {
            if ($this->isTestMode && App::environment('production')) {
                $request->attributes->set('is_production_to_sandbox', true);

                $request->attributes->set('shopify_token_real', $businessShopifyStore->shopify_token);
            }
        }

        return $request;
    }
}
