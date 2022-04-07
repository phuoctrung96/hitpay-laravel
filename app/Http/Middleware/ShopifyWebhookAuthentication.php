<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class ShopifyWebhookAuthentication
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
        $shop = $request->header('x-shopify-shop-domain');

        if (empty($shop)) {
            App::abort(401, 'Shop domain not found.');
        }

        $string = hash_hmac('sha256', $request->getContent(), Config::get('services.shopify.client_secret'), true);

        if (!hash_equals($request->header('x-shopify-hmac-sha256', ''), base64_encode($string))) {
            App::abort(401, 'Invalid signature.');
        }

        return $next($request);
    }
}