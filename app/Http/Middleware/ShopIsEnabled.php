<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Response;

class ShopIsEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $business = $request->route()->parameter('business');

        if (!$business->shop_state) {
            return Response::view('shop.offline', compact('business'));
        }

        return $next($request);
    }
}
