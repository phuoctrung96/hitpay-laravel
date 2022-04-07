<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
     *
     * @var bool
     */
    // CSRF & XSRF difference description:
    // https://stackoverflow.com/questions/64592253/why-do-token-and-xsrf-token-differ-in-laravel
    // Currently we do not need XSRF since it causes same-name cookie conflict between production and staging/sandbox instances
    protected $addHttpCookie = false;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
        'shopify/payment',
        'shopify/callback/*',
        'payment-gateway/*/checkout',
        'business/*/gateway-provider*',
        'xero/confirm',
		'ecwid/load_form',
		'ecwid/payment',
		'ecwid/save_settings',
		'ecwid/hitpay',
        '*/order/confirm/*',
        'business/verification/callback/*',
        'shopify/charge',
        'shopify/webhook',
        'shopify/refund',
        'shopify/checkout/redirect',
    ];

    // /**
    //  * Determine if the request has a URI that should pass through CSRF verification.
    //  *
    //  * @param \Illuminate\Http\Request $request
    //  *
    //  * @return bool
    //  */
    // protected function inExceptArray($request)
    // {
    //     foreach ($this->except as $except) {
    //         if ($except !== '/') {
    //             $except = trim($except, '/');
    //         }
    //
    //         if ($request->route()->named($except)) {
    //             return true;
    //         }
    //     }
    //
    //     return false;
    // }
}
