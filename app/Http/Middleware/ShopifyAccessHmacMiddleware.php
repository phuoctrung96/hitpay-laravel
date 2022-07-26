<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ShopifyAccessHmacMiddleware
{
    public function handle(\Illuminate\Http\Request $request, Closure $next)
    {
        Log::info(json_encode($request->all()));
        Log::info(json_encode($request->header('HTTP_X_SHOPIFY_HMAC_SHA256')));
        Log::info(json_encode($request->header('X-Shopify-Hmac-SHA256')));

        // https://shopify.dev/apps/webhooks/configuration/https#step-5-verify-the-webhook
        $hmacHeader = $request->header('X-Shopify-Hmac-SHA256');

        $data = $request->getContent();

        if (!$this->verifyWebhook($data, $hmacHeader)) {
            Log::critical("Webhook from {$request->getRequestUri()} not verified with data: {$data} \n hmacHeader: {$hmacHeader}");

            throw new \Symfony\Component\HttpKernel\Exception\HttpException(401);
        }

        return $next($request);
    }

    /**
     * @param string $data
     * @param string $hmacHeader
     * @return bool
     */
    private function verifyWebhook(string $data, string $hmacHeader): bool
    {
        // https://shopify.dev/apps/webhooks/configuration/https#step-5-verify-the-webhook
        $calculatedHmac = base64_encode(
            hash_hmac('sha256', $data, Config::get('service.shopify.client_secret_v2'), true)
        );

        if (hash_equals($hmacHeader, $calculatedHmac)) {
            return true;
        } else {
            Log::info('[shopify hmac not equal] data: ' . $data . ' calculatedHmac: ' . $calculatedHmac . ' hmacHeader: ' . $hmacHeader);

            return false;
        }
    }
}
