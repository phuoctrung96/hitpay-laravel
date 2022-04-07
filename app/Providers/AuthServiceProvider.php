<?php

namespace App\Providers;

use App\Business;
use App\Business\Charge;
use App\AuthenticationCode as OAuthAuthenticationCode;
use App\Client as OAuthClient;
use App\PersonalAccessClient as OAuthPersonalAccessClient;
use App\Policies\BusinessUserPolicy;
use App\RefreshToken as OAuthRefreshToken;
use App\Token as OAuthToken;
use App\Policies\BusinessPolicy;
use App\Policies\UserPolicy;
use App\Policies\ApiKeyPolicy;
use App\Policies\ChargePolicy;
use App\Policies\GatewayProviderPolicy;
use App\Policies\PaymentRequestPolicy;
use App\User;
use App\Business\ApiKey;
use App\Business\GatewayProvider;
use App\Business\PaymentRequest;
use Exception;
use HitPay\User\Authentication\EloquentUserProvider;
use HitPay\User\Authentication\SessionGuard;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Laravel\Passport\Passport;
use Laravel\Passport\RouteRegistrar as PassportRouteRegistrar;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Business::class => BusinessPolicy::class,
        User::class => UserPolicy::class,
        ApiKey::class => ApiKeyPolicy::class,
        Charge::class => ChargePolicy::class,
        GatewayProvider::class => GatewayProviderPolicy::class,
        PaymentRequest::class => PaymentRequestPolicy::class,
        Business\BusinessUser::class => BusinessUserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot() : void
    {
        $this->registerPolicies();

        Auth::provider('user.eloquent', function (Application $app, array $config) {
            if ($config['model'] !== User::class) {
                throw new Exception('The HitPay Eloquent user provider is only applicable to user model.');
            }

            return new EloquentUserProvider($app->make('hash'), $config['model']);
        });

        Auth::extend('user.session', function (Application $app, string $name, array $config) {
            $userProvider = $app->make('auth')->createUserProvider($config['provider']);

            if (get_class($userProvider) !== EloquentUserProvider::class) {
                throw new Exception('Only HitPay Eloquent user provider is applicable to this session guard.');
            }

            $guard = new SessionGuard($name, $userProvider, $app->make('session.store'));

            $guard->setCookieJar($app->make('cookie'));
            $guard->setDispatcher($app->make('events'));
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));

            return $guard;
        });

        Passport::useAuthCodeModel(OAuthAuthenticationCode::class);
        Passport::useClientModel(OAuthClient::class);
        Passport::usePersonalAccessClientModel(OAuthPersonalAccessClient::class);
        Passport::useRefreshTokenModel(OAuthRefreshToken::class);
        Passport::useTokenModel(OAuthToken::class);

        Passport::cookie('hitpay_token');

        Passport::routes();

        Route::post('oauth/clients', [
            'uses'          => '\App\Http\Controllers\ClientController@store',
            'as'            => 'passport.clients.store',
            'middleware'    => ['web', 'auth']
        ]);

        Route::get('oauth/clients/{business_id}', [
            'uses'          => '\App\Http\Controllers\ClientController@forUserBusiness',
            'as'            => 'passport.clients.index.business',
            'middleware'    => ['web', 'auth']
        ]);

        Passport::loadKeysFrom(storage_path());
    }
}
