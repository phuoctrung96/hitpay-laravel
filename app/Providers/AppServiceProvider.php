<?php

namespace App\Providers;

use App\Services\XeroAccounts;
use App\Services\XeroCheckout;
use HitPay\Agent\Agent;
use App\Business\GatewayProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use Laravel\Passport\Passport;
use QuickBooksOnline\API\DataService\DataService;

class AppServiceProvider extends ServiceProvider
{
    const STRIPE_VERSION = '2020-08-27';

    /**
     * Register any application services.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function register() : void
    {
        $request = $this->app->make('request');
        $config = $this->app->make('config');
        $domain = $config->get('app.domain');

        if (Str::length($domain) > 0 && !Str::endsWith($request->getHost(), $domain)) {
            $config->set('session.domain');
        }

        Relation::morphMap([
            'business' => \App\Business::class,
            'business_charge' => \App\Business\Charge::class,
            'business_order' => \App\Business\Order::class,
            'business_payment_card' => \App\Business\PaymentCard::class,
            'business_product' => \App\Business\Product::class,
            'business_product_category' => \App\Business\ProductCategory::class,
            'business_recurring_plan' => \App\Business\RecurringBilling::class,
            'business_recurring_plan_template' => \App\Business\SubscriptionPlan::class,
            'business_role' => \App\Business\Role::class,
            'business_shipping' => \App\Business\Shipping::class,
            'business_tax' => \App\Business\Tax::class,
            'business_wallet' => \App\Business\Wallet::class,
            'business_wallet_transaction' => \App\Business\Wallet\Transaction::class,
            'configuration' => \App\Configuration::class,
            'payment_card' => \App\PaymentCard::class,
            'role' => \App\Role::class,
            'session' => \App\Session::class,
            'subscribed_feature' => \App\SubscribedFeature::class,
            'user' => \App\User::class,
            'verification' => \App\Business\Verification::class,
            'payment_provider' => \App\Business\PaymentProvider::class,
        ]);

        Resource::withoutWrapping();

        $this->app->instance('hitpay.agent', new Agent($request));

        $this->app->singleton('App\Manager\BusinessManagerInterface', function ($app) {
            // Repo should be injected here
            return new \App\Manager\BusinessManager();
        });

        $this->app->singleton('App\Manager\ChargeManagerInterface', function ($app) {
            return new \App\Manager\ChargeManager();
        });

        $this->app->singleton('App\Manager\FactoryPaymentIntentManagerInterface', function ($app) {
            return \App\Manager\FactoryPaymentIntentManager::getInstance();
        });

        $this->app->singleton('App\Manager\CustomerManagerInterface', function ($app) {
            return new \App\Manager\CustomerManager();
        });

        $this->app->singleton('App\Manager\PaymentRequestManagerInterface', function ($app) {
            return new \App\Manager\PaymentRequestManager();
        });

        $this->app->singleton(XeroCheckout::class, function($app) {
            return new XeroCheckout();
        });

        $this->app->bind('xeroAccountsService', XeroAccounts::class);

        $this->app->singleton(\App\Services\Quickbooks\AuthorizationService::class, function($app) {
            return new \App\Services\Quickbooks\AuthorizationService(
                config('services.quickbooks.client_id'),
                config('services.quickbooks.client_secret'),
                config('services.quickbooks.redirect'),
                config('services.quickbooks.oauth_scope'),
                config('services.quickbooks.baseUrl')
            );
        });

        $this->app->singleton(DataService::class, function($app, $params) {
            $dataService = DataService::Configure(array(
                'auth_mode' => 'oauth2',
                'ClientID' => config('services.quickbooks.client_id'),
                'ClientSecret' => config('services.quickbooks.client_secret'),
                'accessTokenKey' => $params['accessToken'],
                'refreshTokenKey' => $params['refreshToken'],
                'QBORealmID' => $params['realmId'],
                'baseUrl' => config('services.quickbooks.baseUrl')
            ));
            $dataService->setLogLocation(storage_path('logs/quickbooks.logs'));
            $dataService->throwExceptionOnError(true);

            return $dataService;
        });

        Passport::ignoreMigrations();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot() : void
    {
        // TODO - 2022-02-20
        //   ----------------->>>
        //   -
        //   We should move the Stripe version setting here.
        //
        // \Stripe\Stripe::setApiVersion(static::STRIPE_VERSION);

        $function = function ($value, array $parameters, array $rules) {

            if (!is_string($value) && !(is_object($value) && method_exists($value, '__toString'))) {
                return false;
            }

            $parameters = collect($parameters)->unique();

            if ($parameters->count()) {
                $rules = Arr::only($rules, $parameters->toArray());
            }

            foreach ($rules as $rule) {
                if (preg_match($rule, $value)) {
                    return true;
                }
            }

            return false;
        };

        ValidatorFacade::extend('mobile_phone_number', function (
            string $attribute, $value, array $parameters, Validator $validator
        ) use ($function) : bool {

            return $function($value, $parameters, [
                'MY' => '/^(\+601([2-4]|[6-9])\d{7})|(\+6011\d{8})$/',
                'SG' => '/^(\+65[89]\d{7})$/',
            ]);
        });

        ValidatorFacade::extend('phone_number', function (
            string $attribute, $value, array $parameters, Validator $validator
        ) use ($function) : bool {

            return $function($value, $parameters, [
                'MY' => '/^(\+601([2-4]|[6-9])\d{7})|(\+6011\d{8})|(\+60([3-9])\d{7,9})$/',
                'SG' => '/^(\+65[3689]\d{7})$/',
            ]);
        });

        ValidatorFacade::extend('decimal', function (
            string $attribute, $value, array $parameters, Validator $validator
        ) : bool {

            $validator->requireParameterCount(1, $parameters, $attribute);

            if (filter_var($parameters[0], FILTER_VALIDATE_INT) !== false) {
                if (isset($parameters[1])) {
                    if (filter_var($parameters[1], FILTER_VALIDATE_INT) !== false) {
                        return preg_match('/^\d*(\.\d{'.$parameters[0].','.$parameters[1].'})?$/', $value);
                    }

                    throw new InvalidArgumentException("Validation rule $attribute requires all parameters to be integer.");
                }

                return preg_match('/^\d*(\.\d{'.$parameters[0].'})?$/', $value);
            }

            throw new InvalidArgumentException("Validation rule $attribute requires all parameters to be integer.");
        });
        Queue::failing(function (JobFailed $event) {
            // $event->connectionName
            // $event->job
            // $event->data
//            print_r($event->data);
        });

        ValidatorFacade::extend('unique_name_and_business', function (
            string $attribute, $value, array $parameters, Validator $validator
        ) : bool {
            $provider = GatewayProvider::where('name', $value)
               ->where('business_id', $parameters[0])
               ->first()
            ;

            if ($provider instanceof GatewayProvider) {
                if (count($parameters) == 2 && $provider->getKey() == $parameters[1]) {
                    return true;
                }

                return false;
            }

            return true;
        });
    }
}
