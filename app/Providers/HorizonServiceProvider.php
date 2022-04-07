<?php

namespace App\Providers;

use App\Enumerations\Permission;
use App\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider as ServiceProvider;

class HorizonServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot() : void
    {
        parent::boot();

        Horizon::routeSlackNotificationsTo(Config::get('logging.channels.slack.url'),
            Config::get('logging.channels.slack.channel'));
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate() : void
    {
        Gate::define('viewHorizon', function (User $user) : bool {
            if (!$user->role) {
                return false;
            }

            switch (true) {

                case $user->role->hasPermission(Permission::ALL):
                case $user->role->hasPermission(Permission::MONITOR_HORIZON):
                    return true;
            }

            return false;
        });
    }
}
