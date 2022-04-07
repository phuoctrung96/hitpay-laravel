<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class HasRole
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
        if (!$request->user()->role_id) {
            App::abort(404);
        }

        return $next($request);
    }
}
