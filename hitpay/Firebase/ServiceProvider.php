<?php

namespace HitPay\Firebase;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $config = $this->app['config']['services.firebase'];

        $this->app->when(Channel::class)->needs(Firebase::class)->give(function () use ($config) {
            return new Firebase($config['secret']);
        });
    }
}
