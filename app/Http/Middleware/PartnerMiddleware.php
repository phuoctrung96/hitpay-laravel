<?php

namespace App\Http\Middleware;

use App\Models\BusinessPartner;
use Closure;
use Illuminate\Http\Response;

class PartnerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!$request->user()->businessPartner instanceof BusinessPartner) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
