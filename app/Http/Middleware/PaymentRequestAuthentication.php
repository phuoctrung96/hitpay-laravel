<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use App\Business\ApiKey;
use App\Manager\ApiKeyManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as AuthGuardMiddleware;

class PaymentRequestAuthentication
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
        try {
            $authGuardMiddleware    = app()->make(AuthGuardMiddleware::class);
            $response               = $authGuardMiddleware->handle($request, $next, 'api');

            return $response;
        } catch (AuthenticationException $e) {
            if ($request->headers->has('X-BUSINESS-API-KEY')) {
                $apiKey = ApiKeyManager::findByApiKey($request->header('X-BUSINESS-API-KEY'));
            }

            if (!isset($apiKey) || !$apiKey instanceof ApiKey || !$apiKey->is_enabled) {
                App::abort(404, 'Invalid business api key.');
            }

            Auth::onceUsingId($apiKey->business->owner->id);

            return $next($request);
        }
    }
}
