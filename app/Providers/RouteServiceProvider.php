<?php

namespace App\Providers;

use App\Business;
use App\Business\Charge;
use App\Business\PaymentRequest;
use App\Business\Order;
use App\Business\Product;
use App\Business\ApiKey;
use App\Business\GatewayProvider;
use App\Business\RefundIntent;
use App\Business\RecurringBilling;
use App\BusinessShopifyStore;
use App\Http\Resources\Business\PaymentIntent;
use App\StripeTerminal;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Session;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Bootstrap any application services.
     */
    public function boot() : void
    {
        parent::boot();

        RouteFacade::bind('business', function ($identifier, Route $router) {
            $businesses = Business::where('id', $identifier)->orWhere('identifier', $identifier)->get();

            if (validateUuid($identifier) && ($business = $businesses->where('id', $identifier)->first())) {
                return $business;
            } elseif ($business = $businesses->where('identifier', $identifier)->first()) {
                return $business;
            }

            throw (new ModelNotFoundException)->setModel(Business::class);
        });

        RouteFacade::bind('business_id', function ($identifier, Route $router) {
            $business = Business::find($identifier);
            if ($business)
                return $business;
            else{
                Auth::logout();
            }
        });

        $business = function (string $identifier, Route $router, string $relation) {
            $business = $router->parameter('business_id');

            if (!$business) {
                abort(404);
            }

            if ($business instanceof Business) {
                $related = $business->{$relation}()->find($identifier);

                if ($related instanceof Model) {
                    return $related;
                }
            }

            throw (new ModelNotFoundException)->setModel(get_class($business->{$relation}()->getModel()));
        };

        $product = function (string $identifier, Route $router, string $relation) {
            $product = $router->parameter('b_product');

            if ($product instanceof Product) {
                $related = $product->{$relation}()->find($identifier);

                if ($related instanceof Model) {
                    return $related;
                }
            }

            throw (new ModelNotFoundException)->setModel(get_class($product->{$relation}()->getModel()));
        };

        RouteFacade::bind('b_bank_account', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'bankAccounts');
        });

        RouteFacade::bind('b_charge', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'charges');
        });

        RouteFacade::bind('b_commission', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'commissions');
        });

        RouteFacade::bind('b_transfer', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'transfers');
        });

        RouteFacade::bind('b_payment_intent', function ($identifier, Route $router) use ($business) {
            $charge = $router->parameter('b_charge');

            if ($charge instanceof Charge) {
                $related = $charge->paymentIntents()->find($identifier);

                if ($related instanceof Model) {
                    return $related;
                }
            }

            throw (new ModelNotFoundException)->setModel(PaymentIntent::class);
        });

        RouteFacade::bind('b_refund', function ($identifier, Route $router) use ($business) {
            $charge = $router->parameter('b_charge');

            if ($charge instanceof Charge) {
                $related = $charge->refunds()->withoutGlobalScope('success')->find($identifier);

                if ($related instanceof Model) {
                    return $related;
                }
            }

            throw (new ModelNotFoundException)->setModel(RefundIntent::class);
        });

        RouteFacade::bind('b_refund_intent', function ($identifier, Route $router) use ($business) {
            $charge = $router->parameter('b_charge');

            if ($charge instanceof Charge) {
                $related = $charge->refundIntents()->find($identifier);

                if ($related instanceof Model) {
                    return $related;
                }
            }

            throw (new ModelNotFoundException)->setModel(RefundIntent::class);
        });
        RouteFacade::bind('b_customer', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'customers');
        });

        RouteFacade::bind('b_discount', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'discounts');
        });

        RouteFacade::bind('b_order', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'orders');
        });

        RouteFacade::bind('b_ordered_product', function ($identifier, Route $router) use ($business) {
            $order = $router->parameter('b_order');

            if ($order instanceof Order) {
                $related = $order->products()->find($identifier);

                if ($related instanceof Model) {
                    return $related;
                }
            }

            throw (new ModelNotFoundException)->setModel(get_class($order->products()->getModel()));
        });

        RouteFacade::bind('b_payment_card', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'paymentCards');
        });

        RouteFacade::bind('b_product', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'products');
        });

        RouteFacade::bind('b_product_category', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'productCategories');
        });

        RouteFacade::bind('b_cashback', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'cashbacks');
        });

        RouteFacade::bind('b_product_image', function ($identifier, Route $router) use ($product) {
            return $product($identifier, $router, 'images');
        });

        RouteFacade::bind('b_product_variation', function ($identifier, Route $router) use ($product) {
            return $product($identifier, $router, 'variations');
        });

        RouteFacade::bind('b_shipping', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'shippings');
        });

        RouteFacade::bind('b_shipping_discount', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'shipping_discount');
        });

        RouteFacade::bind('b_tax', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'taxes');
        });

        RouteFacade::bind('b_tax_setting', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'tax_settings');
        });

        RouteFacade::bind('b_recurring_billings', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'recurringBillings');
        });

        RouteFacade::bind('b_invoice', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'invoices');
        });

        RouteFacade::bind('b_subscription_plan', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'subscriptionPlans');
        });

        RouteFacade::bind('charge_id', function ($identifier, Route $router) {
            $business = $router->parameter('business_id');

            if (!$business instanceof Business) {
                $business = $router->parameter('business');
            }

            if ($business instanceof Business) {
                $related = $business->charges()->find($identifier);

                if ($related instanceof Charge) {
                    return $related;
                }
            }

            throw (new ModelNotFoundException)->setModel(Charge::class);
        });

        RouteFacade::bind('product_id', function ($identifier, Route $router) {
            $business = $router->parameter('business_id');

            if (!$business instanceof Business) {
                $business = $router->parameter('business');
            }

            if ($business instanceof Business) {
                $related = $business->products()->find($identifier);

                if ($related instanceof Product) {
                    return $related;
                }
            }

            throw (new ModelNotFoundException)->setModel(Product::class);
        });

        RouteFacade::bind('order_id', function ($identifier, Route $router) {
            $business = $router->parameter('business_id');

            if (!$business instanceof Business) {
                $business = $router->parameter('business');
            }

            if ($business instanceof Business) {
                $related = $business->orders()->find($identifier);

                if ($related instanceof Order) {
                    return $related;
                }
            }

            throw (new ModelNotFoundException)->setModel(Order::class);
        });

        RouteFacade::bind('recurring_plan_id', function ($identifier, Route $router) {
            $business = $router->parameter('business_id');

            if (!$business instanceof Business) {
                $business = $router->parameter('business');
            }

            if ($business instanceof Business) {
                $related = $business->recurringBillings()->find($identifier);

                if ($related instanceof RecurringBilling) {
                    return $related;
                }
            }

            throw (new ModelNotFoundException)->setModel(RecurringBilling::class);
        });

        RouteFacade::bind('terminal_id', function ($identifier, Route $router) {
            $business = $router->parameter('business_id');

            if (!$business instanceof Business) {
                $business = $router->parameter('business');
            }

            if ($business instanceof Business) {
                $related = $business->stripeTerminals()->find($identifier);

                if ($related instanceof StripeTerminal) {
                    return $related;
                }
            }

            throw (new ModelNotFoundException)->setModel(StripeTerminal::class);
        });

        RouteFacade::bind('user_id', function ($identifier, Route $router) {
            return User::findOrFail($identifier);
        });

        RouteFacade::bind('api_key_id', function ($identifier, Route $router) {
            return ApiKey::findOrFail($identifier);
        });

        RouteFacade::bind('gateway_provider_id', function ($identifier, Route $router) {
            return GatewayProvider::findOrFail($identifier);
        });

        RouteFacade::bind('cashback_campaign_id', function ($identifier, Route $router) {
            return Business\CashbackCampaign::findOrFail($identifier);
        });

        RouteFacade::bind('business_slug', function ($identifier, Route $router) {
            $identifier = str_replace('@', '', $identifier);
            $business   = Business::where('slug', $identifier)->first();

            if ($business instanceof Business) {
                return $business;
            }

            throw (new ModelNotFoundException)->setModel(Business::class);
        });

        RouteFacade::bind('payment_request_id', function ($identifier, Route $router) {
            return PaymentRequest::findOrFail($identifier);
        });

        RouteFacade::bind('p_charge', function ($identifier, Route $router) {
            return Charge::findOrFail($identifier);
        });

        RouteFacade::bind('b_verification', function ($identifier, Route $router) use ($business) {
            return $business($identifier, $router, 'verifications');
        });

        RouteFacade::bind('b_shopify_store', function ($identifier, Route $router) use ($business) {
            return BusinessShopifyStore::findOrFail($identifier);
        });
    }

    /**
     * Define the routes for the application.
     */
    public function loadRoutes() : void
    {
        // These routes are typically stateless.

        RouteFacade::domain(Config::get('app.subdomains.api'))
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));

        RouteFacade::domain(Config::get('app.subdomains.pos'))
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/migrated-api.php'));

        RouteFacade::domain(Config::get('app.subdomains.api'))
            ->middleware('client_credentials')
            ->namespace($this->namespace)
            ->group(base_path('routes/client_credentials.php'));


        // These routes all receive session state, CSRF protection, etc.
        RouteFacade::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }
}
