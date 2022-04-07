<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot() : void
    {
        $appName = Config::get('app.name');

        View::share('app_name', $appName);
        View::share('company_name', 'HitPay Payment Solutions Pte Ltd');
        View::share('external_links', [
            'facebook' => [
                'name' => $appName,
                'url' => 'https://www.facebook.com/HitPaynow/',
            ],
            'instagram' => [
                'name' => $appName,
                'url' => 'https://www.instagram.com/hitpay.sg/',
            ],
        ]);
        View::share('hitpay_script_variables', [
            'app_name' => $appName,
            'scheme' => Request::getScheme(),
            'domain' => Config::get('app.domain'),
            'subdomains' => [
                'admin' => Config::get('app.subdomains.admin'),
                'api' => Config::get('app.subdomains.api'),
                'dashboard' => Config::get('app.subdomains.dashboard'),
                'shop' => Config::get('app.subdomains.shop'),
                'invoice' => Config::get('app.subdomains.invoice'),
                'securecheckout' => Config::get('app.subdomains.securecheckout'),
            ],
            'shop_domain' => Config::get('app.shop_domain'),
            'payment_gateway'   => [
                'interval'  => Config::get('app.payment_gateway.status_check_interval'),
                'timeout'   => Config::get('app.payment_gateway.status_check_timeout'),
            ],
            'pusher' => [
                'key' => Config::get('broadcasting.connections.pusher.key'),
                'cluster' => Config::get('broadcasting.connections.pusher.options.cluster'),
            ],
            'posthog' => [
                'api_key' => Config::get('services.posthog.api_key'),
                'api_host' => Config::get('services.posthog.api_host'),
            ]
        ]);
    }
}
