<?php

namespace App\Console\Commands;

use App\Enumerations\Business\Channel;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\Event;
use App\Enumerations\Business\ImageGroup;
use App\Enumerations\Business\NotificationChannel;
use App\Enumerations\Business\OrderStatus;
use App\Enumerations\CountryCode;
use App\Enumerations\Image\Size;
use App\Enumerations\PaymentProvider;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Constraint as ImageConstraint;
use Intervention\Image\Facades\Image as ImageFacade;
use stdClass;
use Stripe\Account as StripeAccount;
use Stripe\BalanceTransaction;
use Stripe\Charge;
use Stripe\Customer as StripeCustomer;
use Stripe\Exception\PermissionException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Transfer;
use Throwable;

class MigrateDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate database from HitPay';

    private $oldConnection;

    private $newConnection;

    private $startTime;

    private $filename;

    private $accountsCollection;

    private $client;

    private $totalUserDetected;

    private $totalUserProcessed = 1;

    /**
     * The configurations for different image type.
     *
     * @var array
     */
    private $configurations = [
        ImageGroup::LOGO => [
            Size::ORIGINAL => null,
            Size::LARGE => 1024,
            Size::MEDIUM => 512,
            Size::SMALL => 256,
            Size::THUMBNAIL => 128,
            Size::ICON => 64,
        ],
        ImageGroup::PRODUCT => [
            Size::ORIGINAL => null,
            Size::LARGE => 2048,
            Size::MEDIUM => 1024,
            Size::SMALL => 512,
            Size::THUMBNAIL => 256,
            Size::ICON => 128,
        ],
    ];

    /**
     * MigrateMasterDataNew constructor.
     */
    public function __construct()
    {
        ini_set('memory_limit', -1);

        parent::__construct();

        $this->startTime = Date::now();

        $this->oldConnection = DB::connection('mysql_old');
        $this->oldConnection->enableQueryLog();

        $this->newConnection = DB::connection('mysql');
        $this->newConnection->enableQueryLog();

        $this->filename = $this->startTime->toDayDateTimeString().'.txt';
        $this->accountsCollection = Collection::make();
        $this->client = new Client;

        // We have to use live key here.

        Stripe::setApiKey('sk_live_LxmLhMShkMUAEJCgP37rCjNO');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->totalUserDetected = str_pad($this->oldConnection->table('accounts')->count(), 6, ' ');

        $this->oldConnection->table('oauth_clients')->get()->each(function ($item) {
            $this->newConnection->table('oauth_clients')->insert((array) $item);
        });

        $this->oldConnection->table('oauth_personal_access_clients')->get()->each(function ($item) {
            $this->newConnection->table('oauth_personal_access_clients')->insert((array) $item);
        });

        $accountsTable = $this->oldConnection->table('accounts');
        $accountsTable->orderBy('id')->each(function (stdClass $item) {
            $this->totalUserProcessed++;

            Stripe::$accountId = null;

            $customers = Collection::make();

            $this->line('');
            $this->listStatus('<info>'.$item->name.'</info>', true);

            $businessCountry = strtolower($item->country_code);
            $businessCurrency = strtolower($item->default_currency_code);

            if ($businessCountry !== CountryCode::SINGAPORE) {
                return $this->exceptedBusiness('unaccepted_country', $item);
            }

            try {
                $stripeAccount = StripeAccount::retrieve($item->auth_id);
            } catch (PermissionException $exception) {
                if ($exception->getStripeCode() !== 'account_invalid') {
                    return $this->exceptedBusiness('unexpected:'.$exception->getStripeCode(), $item);
                }

                return $this->exceptedBusiness('stripe_account_invalid', $item);
            }

            $extraData = json_decode($item->extra_data, true);

            /** @var \App\User $user */
            $user = new stdClass;
            $user->id = $this->getNewUuid();
            $user->email_login_enabled = false;
            $user->locale = App::getLocale();
            $user->created_at = $item->created_at;
            $user->updated_at = $item->created_at;

            $this->newConnection->table('users')->insert((array) $user);
            $this->listStatus('User created');

            /** @var \App\Business $business */
            $business = new stdClass;
            $business->id = $item->id;
            $business->identifier = $item->username;
            $business->user_id = $user->id;
            $business->payment_provider = PaymentProvider::STRIPE_SINGAPORE;

            if (!App::isProduction()) {
                Stripe::setApiKey(config('services.stripe.sg.secret'));
            }

            $stripeCustomer = StripeCustomer::create([
                'description' => 'business_id:'.$business->id,
            ]);

            if (!App::isProduction()) {
                Stripe::setApiKey('sk_live_LxmLhMShkMUAEJCgP37rCjNO');
            }

            Storage::append($this->filename, 'Stripe Customer Account ID'.$stripeCustomer->id);
            $this->listStatus('Stripe customer account created: '.$stripeCustomer->id);

            $business->payment_provider_customer_id = $stripeCustomer->id;
            $business->name = $item->name;
            $business->display_name = $item->display_name;
            $business->email = $item->email;
            $business->phone_number = $item->phone_number;
            $business->country = $businessCountry;
            $business->category = $item->business_category;
            $business->statement_description = $item->statement_descriptor;
            $business->locale = App::getLocale();
            $business->currency = $businessCurrency;
            $business->can_pick_up = false;
            $business->migrated = true;
            $business->created_at = $item->created_at;
            $business->updated_at = $item->updated_at;

            if (key_exists('store_address', $extraData)) {
                $business->street = $extraData['store_address'];
                $business->city = 'Singapore';
                $business->state = 'Singapore';
                $business->postal_code = null;
            }

            $this->newConnection->table('businesses')->insert((array) $business);
            $this->listStatus('Business created');

            /** @var \App\Business\Role $role */
            $role = new stdClass;
            $role->id = $this->getNewUuid();
            $role->business_id = $business->id;
            $role->title = 'Administrator';
            $role->description = 'This role has complete access to all objects, folders, role templates, and groups in the business.';
            $role->created_at = Date::now()->toDateTimeString();
            $role->updated_at = $role->created_at;

            $this->newConnection->table('business_roles')->insert((array) $role);

            $assignedRole = new stdClass;
            $assignedRole->business_role_id = $role->id;
            $assignedRole->user_id = $user->id;

            $this->newConnection->table('business_assigned_roles')->insert((array) $assignedRole);

            /** @var \App\Business\PaymentProvider $paymentProvider */
            $paymentProvider = new stdClass;
            $paymentProvider->id = $this->getNewUuid();
            $paymentProvider->business_id = $business->id;
            $paymentProvider->payment_provider = PaymentProvider::STRIPE_SINGAPORE;
            $paymentProvider->payment_provider_account_id = $item->auth_id;
            $paymentProvider->stripe_publishable_key = $extraData['stripe']['token']['stripe_publishable_key'] ?? null;
            $paymentProvider->token_type = $extraData['stripe']['token']['token_type'] ?? null;
            $paymentProvider->access_token = $item->auth_token;
            $paymentProvider->refresh_token = $extraData['stripe']['token']['refresh_token'] ?? null;
            $paymentProvider->token_scopes = $extraData['stripe']['token']['scope'] ?? null;

            $stripeAccount = $stripeAccount->toArray();

            if (isset($stripeAccount['country'])) {
                $stripeAccount['country'] = strtolower($stripeAccount['country']);
            }

            if (isset($stripeAccount['support_address']['country'])) {
                $stripeAccount['support_address']['country'] = strtolower($stripeAccount['support_address']['country']);
            }

            $paymentProvider->data = json_encode($stripeAccount);

            $this->newConnection->table('business_payment_providers')->insert((array) $paymentProvider);

            if (!$paymentProvider->stripe_publishable_key) {
                $this->listStatus('No publishable key for '.$paymentProvider->payment_provider_account_id.'');
            }

            $this->listStatus('Payment provider created');

            if (key_exists('logo', $extraData)) {
                try {
                    $logo = $this->client->get('https://pos.hit-pay.com/logo/'.$item->id);
                    $image = $this->processImage('logo', $logo->getBody()->getContents());

                    /** @var \App\Business\Image|null $image */
                    if (!is_null($image)) {
                        $image->id = $this->getNewUuid();
                        $image->business_id = $business->id;
                        $image->created_at = $item->created_at;
                        $image->updated_at = $item->created_at;

                        $this->newConnection->table('business_images')->insert((array) $image);
                        $this->listStatus('Logo image created');
                    }
                } catch (Throwable $exception) {
                    $this->warn(sprintf('Logo [https://pos.hit-pay.com/logo/%s] not downloaded: %s:%d => %s',
                        $item->id, get_class($exception), $exception->getLine(), $exception->getMessage()));
                }
            }

            $accessTokensQuery = $this->oldConnection->table('oauth_access_tokens')->where('user_id', $business->id);
            $accessTokensQuery->orderBy('id')->each(function (stdClass $item) use ($user) {
                $item = (array) $item;

                $expires = Date::createFromTimeString($item['expires_at']);

                if ($expires->isPast()) {
                    return $item;
                }

                $item['user_id'] = $user->id;
                $item['request_ip_address'] = '127.0.0.1';

                $this->newConnection->table('oauth_access_tokens')->insert((array) $item);

                $refreshTokensQuery = $this->oldConnection->table('oauth_refresh_tokens');
                $refreshTokensQuery->where('access_token_id', $item['id'])->orderBy('id')->each(function ($item) {
                    $this->newConnection->table('oauth_refresh_tokens')->insert((array) $item);
                });

                return $item;
            });
            $this->listStatus('OAuth access and refresh tokens migrated.');

            $shippingsQuery = $this->oldConnection->table('shipping_methods')->where('account_id', $item->id);
            $shippingsQuery->orderBy('id')->each(function (stdClass $item) use ($business) {
                /** @var \App\Business\Shipping $shipping */
                $shipping = new stdClass;
                $shipping->id = $item->id;
                $shipping->business_id = $business->id;
                $shipping->calculation = $item->calculation;
                $shipping->name = $item->method_name;
                $shipping->rate = $item->amount;
                $shipping->active = true;
                $shipping->created_at = $business->updated_at;
                $shipping->updated_at = $shipping->created_at;

                $this->newConnection->table('business_shippings')->insert((array) $shipping);

                if ($item->country_code !== 'GLOBAL') {
                    /** @var \App\Business\ShippingCountry $shippingCountry */
                    $shippingCountry = new stdClass;
                    $shippingCountry->business_shipping_id = $shipping->id;
                    $shippingCountry->country = strtolower($item->country_code);

                    $this->newConnection->table('business_shipping_countries')->insert((array) $shippingCountry);
                }
            });
            $this->listStatus('Shipping methods migrated.');

            $subscribedEventsQuery = $this->oldConnection->table('subscriptions')->where('user_id', $item->id);
            $subscribedEventsQuery->orderBy('method')->each(function ($item) use ($business) {
                /** @var \App\Business\SubscribedEvent $subscribedEvent */
                $subscribedEvent = new stdClass;
                $subscribedEvent->business_id = $business->id;

                if ($item->event === 'new_checkout_order') {
                    $subscribedEvent->event = Event::NEW_ORDER;
                } elseif ($item->event === 'low_quantity') {
                    $subscribedEvent->event = Event::LOW_QUANTITY_ALERT;
                } else {
                    $subscribedEvent->event = $item->event;
                }

                if ($item->method === 'mobile_notification') {
                    $subscribedEvent->channel = NotificationChannel::PUSH_NOTIFICATION;
                } else {
                    $subscribedEvent->channel = $item->method;
                }

                $this->newConnection->table('business_subscribed_events')->insert((array) $subscribedEvent);
            });
            $this->listStatus('Event subscriptions migrated.');
            $this->listStatus('Migrating products and variations');
            $this->line('');

            $productsCountQuery = $this->oldConnection->table('products')->where('account_id', $item->id);
            $bar = $this->output->createProgressBar($productsCountQuery->whereNull('parent_id')->count());
            $bar->start();

            $this->oldConnection->table('products')->where('account_id', $item->id)->whereNull('parent_id')
                ->orderBy('id')->each(function (stdClass $item) use ($business, &$bar) {
                    $bar->advance();

                    if ($item->shopify_id) {
                        return $item;
                    } elseif ($business->currency !== strtolower($item->currency_code)) {
                        return $item;
                    }

                    /** @var \App\Business\Product $product */
                    $product = new stdClass;
                    $product->id = $item->id;
                    $product->business_id = $business->id;
                    $product->name = $item->name;
                    $product->description = $item->remark;
                    $product->currency = $business->currency;
                    $product->price = $item->amount;
                    $product->created_at = $item->created_at;
                    $product->updated_at = $item->updated_at;

                    if ($item->is_manageable) {
                        $product->quantity = 1;
                    } else {
                        $product->quantity = null;
                    }

                    if ($item->deleted_at) {
                        $product->published_at = null;
                    } else {
                        $product->published_at = $item->activated_at;
                    }

                    $hasVariations = $this->oldConnection->table('products')->where('parent_id', $item->id)
                        ->whereNull('deleted_at')->count();

                    if ($hasVariations > 0) {
                        $product->variation_key_1 = $item->variant_1_key;
                        $product->variation_key_2 = $item->variant_2_key;
                        $product->variation_key_3 = $item->variant_3_key;

                        $this->newConnection->table('business_products')->insert((array) $product);

                        $variationsQuery = $this->oldConnection->table('products')->where('parent_id', $item->id);
                        $variationsQuery->whereNull('deleted_at');
                        $variationsQuery->orderBy('id')->each(function (stdClass $item) use ($product) {
                            /** @var \App\Business\ProductVariation $variation */
                            $variation = new stdClass;
                            $variation->id = $item->id;
                            $variation->business_id = $product->business_id;
                            $variation->parent_id = $product->id;
                            $variation->description = implode(' / ', array_filter([
                                $item->variant_1_value,
                                $item->variant_2_value,
                                $item->variant_3_value,
                            ]));
                            $variation->variation_value_1 = $item->variant_1_value;
                            $variation->variation_value_2 = $item->variant_2_value;
                            $variation->variation_value_3 = $item->variant_3_value;

                            if ($product->quantity === 1) {
                                $variation->quantity = $item->quantity;
                                $variation->quantity_alert_level = $item->quantity_alert;
                            }

                            $variation->price = $item->amount !== null ? $item->amount : $product->price;
                            $variation->created_at = $item->created_at;
                            $variation->updated_at = $item->updated_at;
                            $variation->published_at = $item->activated_at;

                            $this->newConnection->table('business_products')->insert((array) $variation);

                            return $item;
                        });
                    } else {
                        /** @var \App\Business\ProductVariation $variation */
                        $variation = new stdClass;
                        $variation->id = $product->id;
                        $product->id = $this->getNewUuid();
                        $variation->parent_id = $product->id;
                        $variation->business_id = $product->business_id;

                        if ($product->quantity === 1) {
                            $variation->quantity = $item->quantity;
                            $variation->quantity_alert_level = $item->quantity_alert;
                        }

                        $variation->price = $item->amount;
                        $variation->created_at = $item->created_at;
                        $variation->updated_at = $item->updated_at;
                        $variation->published_at = $item->activated_at;

                        $this->newConnection->table('business_products')->insert((array) $product);
                        $this->newConnection->table('business_products')->insert((array) $variation);
                    }

                    // craete shortcut
                    if ($item->short_url) {
                        /** @var \App\Shortcut $shortcut */
                        $shortcut = new stdClass;
                        $shortcut->id = $item->short_url;
                        $shortcut->route_name = 'shop.product';
                        $shortcut->parameters = json_encode([
                            'business' => $business->id,
                            'product_id' => $product->id,
                        ]);
                        $this->newConnection->table('shortcuts')->insert((array) $shortcut);
                    }

                    if (!$item->default_image_id) {
                        return $item;
                    }

                    $productImagesQuery = $this->oldConnection->table('product_images');
                    $productImagesQuery = $productImagesQuery->where('id', $item->default_image_id)->orderBy('id');
                    $productImagesQuery->each(function (stdClass $item) use ($product, $business) {
                        try {
                            $image = $this->client->get('https://checkout.hit-pay.com/product/image/'.$item->id);
                            $image = $this->processImage('product', $image->getBody()->getContents());

                            /** @var \App\Business\Image|null $image */
                            if (!is_null($image)) {
                                $image->id = $this->getNewUuid();
                                $image->business_id = $business->id;
                                $image->business_associable_id = $product->id;
                                $image->business_associable_type = 'business_product';
                                $image->created_at = $item->created_at;
                                $image->updated_at = $item->created_at;

                                $this->newConnection->table('business_images')->insert((array) $image);
                            }
                        } catch (Throwable $exception) {
                            $this->warn(sprintf('Product image [https://pos.hit-pay.com/logo/%s] not downloaded: %s:%d => %s',
                                $item->id, get_class($exception), $exception->getLine(), $exception->getMessage()));
                            $this->line('');
                        }
                    });

                    return $item;
                });

            $bar->finish();
            $this->line('');
            $this->line('');
            $this->listStatus('Products and variations migrated');
            $this->listStatus('Migrating orders');
            $this->line('');

            $ordersCount = $this->oldConnection->table('orders')->where('account_id', $item->id)->count();
            $bar = $this->output->createProgressBar($ordersCount);
            $bar->start();

            $ordersQuery = $this->oldConnection->table('orders')->where('account_id', $item->id)->orderBy('id');
            $ordersQuery->each(function (stdClass $item) use ($business, &$bar, &$customers) {
                $bar->advance();
                $extraData = json_decode($item->extra_data, true);

                /** @var \App\Business\Order $order */
                $order = new stdClass;
                $order->id = $item->id;
                $order->business_id = $business->id;
                $order->customer_name = $item->buyer_name;
                $order->customer_email = $item->buyer_email;

                if (key_exists('shipping_address', $extraData)) {
                    $order->customer_street = $extraData['shipping_address']['line'] ?? null;
                    $order->customer_city = $extraData['shipping_address']['city'] ?? null;
                    $order->customer_state = $extraData['shipping_address']['state'] ?? null;
                    $order->customer_postal_code = $extraData['shipping_address']['postal_code'] ?? null;
                    $order->customer_country = strtolower($extraData['shipping_address']['country_code'] ?? null);
                }

                $order->customer_pickup = $item->is_picking_up;
                $order->channel = 'untraceable';
                $order->currency = strtolower($item->currency_code);
                $order->shipping_amount = $item->shipping_amount === null ? 0 : $item->shipping_amount;
                $order->amount = $item->amount + $order->shipping_amount;
                $order->line_item_price = $order->amount;
                $order->remark = $item->remark;

                switch ($item->status) {

                    case 'cancelled':
                        $order->status = OrderStatus::CANCELED;
                        break;

                    case 'pending':
                        $order->status = OrderStatus::REQUIRES_PAYMENT_METHOD;
                        break;

                    case 'paid':
                        $order->status = OrderStatus::REQUIRES_BUSINESS_ACTION;
                        break;

                    default:
                        $order->status = $item->status;
                }

                $order->request_ip_address = '127.0.0.1';
                $order->created_at = $item->created_at;
                $order->updated_at = $item->updated_at;
                $order->closed_at = $item->completed_at;

                $this->newConnection->table('business_orders')->insert((array) $order);

                $orderedProductQuery = $this->oldConnection->table('ordered_products');
                $orderedProductQuery = $orderedProductQuery->where('order_id', $item->id);
                $orderedProductQuery->orderBy('id')->each(function (stdClass $item) use ($order) {
                    $extraData = json_decode($item->extra_data, true);

                    /** @var \App\Business\OrderedProduct $orderedProduct */
                    $orderedProduct = new stdClass;
                    $orderedProduct->id = $item->id;
                    $orderedProduct->business_order_id = $order->id;
                    $orderedProduct->business_product_id = $item->variation_id ?? $item->product_id;
                    $orderedProduct->quantity = $item->quantity;
                    $orderedProduct->unit_price = $item->amount;
                    $orderedProduct->price = $orderedProduct->quantity * $orderedProduct->unit_price;
                    $orderedProduct->remark = $item->note;
                    $orderedProduct->created_at = $order->created_at;
                    $orderedProduct->updated_at = $orderedProduct->created_at;

                    $tempProduct = $extraData['objects']['App\\Product'];

                    $orderedProduct->name = $tempProduct['name'];

                    if ($item->variation_id) {
                        $tempVariation = $extraData['objects']['App\\Variation'];
                        $orderedProduct->description = $tempVariation['name'];
                        $orderedProduct->variation_key_1 = $tempProduct['variant_1_key'] ?? null;
                        $orderedProduct->variation_value_1 = $tempVariation['variant_1_value'] ?? null;
                        $orderedProduct->variation_key_2 = $tempProduct['variant_2_key'] ?? null;
                        $orderedProduct->variation_value_2 = $tempVariation['variant_1_value'] ?? null;
                        $orderedProduct->variation_key_3 = $tempProduct['variant_3_key'] ?? null;
                        $orderedProduct->variation_value_3 = $tempVariation['variant_1_value'] ?? null;
                    }

                    $this->newConnection->table('business_ordered_products')->insert((array) $orderedProduct);
                });

                if ($order->customer_email && $customers->where('email', $order->customer_email)->count() === 0) {
                    /** @var \App\Business\Customer $customer */
                    $customer = new stdClass;
                    $customer->id = $this->getNewUuid();
                    $customer->business_id = $order->business_id;
                    $customer->email = $order->customer_email;
                    $customer->name = $order->customer_name ?? null;
                    $customer->street = $order->customer_street ?? null;
                    $customer->state = $order->customer_state ?? null;
                    $customer->city = $order->customer_city ?? null;
                    $customer->postal_code = $order->customer_postal_code ?? null;
                    $customer->country = $order->customer_country ?? null;
                    $customer->created_at = Date::now()->toDateTimeString();
                    $customer->updated_at = $customer->created_at;

                    try {
                        $this->newConnection->table('business_customers')->insert((array) $customer);
                    } catch (Exception $exception) {
                        // DO NOTHING
                    }

                    $customers->add([
                        'email' => $customer->email,
                    ]);
                }
            });

            $bar->finish();
            $this->line('');
            $this->line('');
            $this->listStatus('Orders migrated');
            $this->listStatus('Migrating charges');
            $this->line('');

            $transactionsQuery = $this->oldConnection->table('transactions')->where('account_id', $item->id);
            $transactionsCount = $transactionsQuery->count();
            $bar = $this->output->createProgressBar($transactionsCount);
            $bar->start();

            $transactionsQuery->orderBy('id')->each(function (stdClass $item) use (
                $business, $paymentProvider, &$bar, &$customers
            ) {
                $bar->advance();

                Stripe::$accountId = null;

                $extraData = json_decode($item->extra_data, true);

                /** @var \App\Business\Charge $chargeModel */
                $chargeModel = new stdClass;
                $chargeModel->id = $item->id;
                $chargeModel->business_id = $business->id;

                if (Str::startsWith($item->path_info, '/order')) {
                    $chargeModel->channel = Channel::STORE_CHECKOUT;
                } else {
                    $chargeModel->channel = Channel::POINT_OF_SALE;
                }

                $chargeModel->currency = strtolower($item->currency_code);
                $chargeModel->amount = $item->amount;

                if ($item->order_id) {
                    $chargeModel->business_target_type = 'business_order';
                    $chargeModel->business_target_id = $item->order_id;

                    /** @var \App\Business\Order $order */
                    $order = $this->newConnection->table('business_orders')->find($chargeModel->business_target_id);

                    $chargeModel->customer_name = $order->customer_name;
                    $chargeModel->customer_email = $order->customer_email;
                    $chargeModel->customer_phone_number = $order->customer_phone_number;
                    $chargeModel->customer_street = $order->customer_street;
                    $chargeModel->customer_city = $order->customer_city;
                    $chargeModel->customer_state = $order->customer_state;
                    $chargeModel->customer_postal_code = $order->customer_postal_code;
                    $chargeModel->customer_country = $order->customer_country;
                }

                switch ($item->method) {

                    case 'payment_card':
                        $chargeModel->payment_provider_charge_method = 'card';
                        break;

                    default:
                        $chargeModel->payment_provider_charge_method = $item->method;
                }

                if ($item->charge_type === 'destination_charge') {
                    $chargeModel->payment_provider_transfer_type = 'destination';
                } elseif ($item->charge_type === 'direct_charge') {
                    $chargeModel->payment_provider_transfer_type = 'direct';
                } else {
                    $chargeModel->payment_provider_transfer_type = null;
                }

                switch ($item->status) {

                    case 'completed':
                        $chargeModel->status = ChargeStatus::SUCCEEDED;
                        break;

                    case 'cancelled':
                        $chargeModel->status = ChargeStatus::VOID;
                        break;

                    default:
                        $chargeModel->status = $item->status;
                }

                $chargeModel->remark = $extraData['remark'] ?? $item->remark;
                $chargeModel->failed_reason = $item->reason_code;
                $chargeModel->request_ip_address = $item->ip_address;
                $chargeModel->created_at = $item->created_at;
                $chargeModel->updated_at = $item->updated_at;

                if ($item->platform === 'stripe') {
                    $chargeModel->payment_provider = 'stripe_sg';

                    if ($chargeModel->payment_provider_transfer_type === 'destination') {
                        // This is assuming that all charges in previous system have charges.
                        try {
                            if (isset($extraData['objects']['charge'])) {
                                $stripeCharge = Charge::retrieve($extraData['objects']['charge']['id']);
                            } elseif (isset($extraData['stripe']['payment_intent'])) {
                                $stripePaymentIntent =
                                    PaymentIntent::retrieve($extraData['stripe']['payment_intent']['id']);
                                if ($chargeModel->status === 'succeeded' || $chargeModel->status === 'refunded') {
                                    $stripeCharge = $stripePaymentIntent->charges->data[0];
                                }
                            } elseif (isset($extraData['stripe']['charge'])) {
                                $stripeCharge = Charge::retrieve($extraData['stripe']['charge']['id']);
                            }
                        } catch (Throwable $exception) {
                            dump([
                                'Business ID' => $business->id,
                                'Stripe Account ID' => $paymentProvider->payment_provider_account_id,
                                'Charge ID' => $chargeModel->id,
                                'Extra Data Extracted' => $extraData,
                            ]);

                            throw $exception;
                        }

                        $chargeModel->payment_provider_account_id = $paymentProvider->payment_provider_account_id;
                        $chargeModel->payment_provider_charge_id = $stripeCharge->id ?? null;
                        $chargeModel->payment_provider_charge_type = $stripeCharge->object ?? null;
                        $chargeModel->data = isset($stripeCharge)
                            ? json_encode($stripeCharge->toArray())
                            : (isset($stripePaymentIntent)
                                ? json_encode($stripePaymentIntent->toArray())
                                : null);

                        // todo calculate fee rate
                        if (isset($extraData['objects']['transfer']['id'])) {
                            $transferId = $extraData['objects']['transfer']['id'];
                            $step = 1;
                        } elseif (isset($extraData['stripe']['transfer']['id'])) {
                            $transferId = $extraData['stripe']['transfer']['id'];
                            $step = 2;
                        } elseif (isset($stripeCharge->transfer)) {
                            $transferId = $stripeCharge->transfer;
                            $step = 3;
                        } else {
                            $transferId = null;
                            $step = 4;
                        }

                        if ($chargeModel->status === 'succeeded' || $chargeModel->status === 'refunded') {
                            if (is_null($transferId)) {
                                dd($chargeModel->id, $step, $transferId, $stripeCharge, $extraData);
                            }

                            $stripeTransfer = Transfer::retrieve($transferId);
                            $stripeBalance = BalanceTransaction::retrieve($stripeCharge->balance_transaction);

                            $chargeModel->home_currency = $stripeBalance->currency;
                            $chargeModel->home_currency_amount = $stripeBalance->amount;

                            /** @var \App\Business\Transfer $transferModel */
                            $transferModel = new stdClass;
                            $transferModel->id = $this->getNewUuid();
                            $transferModel->business_id = $chargeModel->business_id;
                            $transferModel->business_charge_id = $chargeModel->id;
                            $transferModel->payment_provider = $chargeModel->payment_provider;
                            $transferModel->payment_provider_account_id = $chargeModel->payment_provider_account_id;
                            $transferModel->payment_provider_transfer_type = $stripeTransfer->object;
                            $transferModel->payment_provider_transfer_id = $stripeTransfer->id;
                            $transferModel->payment_provider_transfer_method = 'destination';
                            $transferModel->currency = $stripeTransfer->currency;
                            $transferModel->amount = $stripeTransfer->amount;
                            $transferModel->status = 'succeeded';
                            $transferModel->data = json_encode($stripeTransfer->toArray());
                            $transferModel->request_ip_address = '127.0.0.1';
                            $transferModel->created_at = $chargeModel->created_at;
                            $transferModel->updated_at = $transferModel->created_at;

                            if ($chargeModel->payment_provider_charge_method === 'card') {
                                $chargeModel->fixed_fee = $item->fixed_charge + 50;
                                $chargeModel->discount_fee_rate = bcdiv(bcadd(3.4, $item->discount_charge, 4), 100, 4);
                                $chargeModel->discount_fee =
                                    bcmul($chargeModel->home_currency_amount, $chargeModel->discount_fee_rate);
                            } elseif ($chargeModel->payment_provider_charge_method === 'wechat'
                                || $chargeModel->payment_provider_charge_method === 'alipay') {
                                $chargeModel->fixed_fee = $item->fixed_charge + 35;
                                $chargeModel->discount_fee_rate = bcdiv(bcadd(2.7, $item->discount_charge, 4), 100, 4);
                                $chargeModel->discount_fee =
                                    bcmul($chargeModel->home_currency_amount, $chargeModel->discount_fee_rate);
                            } else {
                                dump($item);

                                throw new Exception('There\'s something wrong with the rate.');
                            }

                            if ($chargeModel->status === 'refunded') {
                                /** @var \Stripe\TransferReversal $transferReversal */
                                $transferReversal = $stripeTransfer->reversals->data[0];

                                /** @var \App\Business\Transfer $transferReversalModel */
                                $transferReversalModel = new stdClass;
                                $transferReversalModel->id = $this->getNewUuid();
                                $transferReversalModel->business_id = $transferModel->business_id;
                                $transferReversalModel->business_charge_id = $chargeModel->id;
                                $transferReversalModel->payment_provider = $transferModel->payment_provider;
                                $transferReversalModel->payment_provider_account_id =
                                    $transferModel->payment_provider_account_id;
                                $transferReversalModel->payment_provider_transfer_type = $transferReversal->object;
                                $transferReversalModel->payment_provider_transfer_id = $transferReversal->id;
                                $transferReversalModel->payment_provider_transfer_method = 'unknown';
                                $transferReversalModel->currency = $transferReversal->currency;
                                $transferReversalModel->amount = $transferReversal->amount;
                                $transferReversalModel->status = 'succeeded';
                                $transferReversalModel->data = json_encode($transferReversal->toArray());
                                $transferReversalModel->request_ip_address = '127.0.0.1';

                                try {
                                    $stripeRefund = Refund::retrieve($stripeCharge->refunds->data[0]['id']);
                                } catch (Throwable $exception) {
                                    dump(Stripe::$apiKey, Stripe::$accountId, $stripeCharge->toArray(),
                                        (array) $chargeModel, (array) $item);

                                    throw $exception;
                                }

                                /** @var \App\Business\Refund $refundModel */
                                $refundModel = new stdClass;
                                $refundModel->id = $this->getNewUuid();
                                $refundModel->business_charge_id = $chargeModel->id;
                                $refundModel->payment_provider = $chargeModel->payment_provider;
                                $refundModel->payment_provider_account_id = $chargeModel->payment_provider_account_id;
                                $refundModel->payment_provider_refund_type = $stripeRefund->object;
                                $refundModel->payment_provider_refund_id = $stripeRefund->id;
                                $refundModel->payment_provider_refund_method =
                                    $chargeModel->payment_provider_charge_method;
                                $refundModel->amount = $stripeRefund->amount;
                                $refundModel->data = json_encode($stripeRefund->toArray());
                                $refundModel->request_ip_address = '127.0.0.1';
                                $refundModel->created_at = $item->refunded_at;
                                $refundModel->updated_at = $refundModel->created_at;
                            }
                        }
                    } elseif ($chargeModel->payment_provider_transfer_type === 'direct') {
                        // Because we are using direct charge, we don't do any conversion rate. Hence, the value
                        // will be the same.
                        $chargeModel->home_currency = $chargeModel->currency;
                        $chargeModel->home_currency_amount = $chargeModel->amount;

                        Stripe::setAccountId($paymentProvider->payment_provider_account_id);

                        try {
                            if (isset($extraData['objects']['charge']['id'])) {
                                $stripeCharge = Charge::retrieve($extraData['objects']['charge']['id']);
                            } elseif (isset($extraData['stripe']['payment_intent']['id'])) {
                                $stripePaymentIntent =
                                    PaymentIntent::retrieve($extraData['stripe']['payment_intent']['id']);
                                if ($chargeModel->status === 'succeeded' || $chargeModel->status === 'refunded') {
                                    $stripeCharge = $stripePaymentIntent->charges->data[0];
                                }
                            } else {
                                throw new Exception('Unknown extra data structure detected.');
                            }
                        } catch (Throwable $exception) {
                            dump([
                                'Business ID' => $business->id,
                                'Stripe Account ID' => $paymentProvider->payment_provider_account_id,
                                'Charge ID' => $chargeModel->id,
                                'Extra Data Extracted' => $extraData,
                            ]);

                            throw $exception;
                        }

                        $chargeModel->payment_provider_account_id = $paymentProvider->payment_provider_account_id;
                        $chargeModel->payment_provider_charge_id = $stripeCharge->id ?? null;
                        $chargeModel->payment_provider_charge_type = $stripeCharge->object ?? null;
                        $chargeModel->data = isset($stripeCharge)
                            ? json_encode($stripeCharge->toArray())
                            : json_encode($stripePaymentIntent->toArray());

                        if ($item->discount_charge < 0) {
                            dump((array) $chargeModel);

                            throw new Exception('The discount rate for this transaction seems incorrect.');
                        }

                        // For direct charge, we charge 1% last time. For all currencies.
                        $chargeModel->fixed_fee = $item->fixed_charge;
                        $chargeModel->discount_fee_rate = bcdiv($item->discount_charge, 100, 4);
                        $chargeModel->discount_fee = bcmul($chargeModel->amount, $chargeModel->discount_fee_rate);

                        if ($chargeModel->status === 'refunded') {
                            $stripeRefund = Refund::retrieve($stripeCharge->refunds->data[0]['id']);

                            /** @var \App\Business\Refund $refundModel */
                            $refundModel = new stdClass;
                            $refundModel->id = $this->getNewUuid();
                            $refundModel->business_charge_id = $chargeModel->id;
                            $refundModel->payment_provider = $chargeModel->payment_provider;
                            $refundModel->payment_provider_account_id = $chargeModel->payment_provider_account_id;
                            $refundModel->payment_provider_refund_type = $stripeRefund->object;
                            $refundModel->payment_provider_refund_id = $stripeRefund->id;
                            $refundModel->payment_provider_refund_method = $chargeModel->payment_provider_charge_method;
                            $refundModel->amount = $stripeRefund->amount;
                            $refundModel->data = json_encode($stripeRefund->toArray());
                            $refundModel->request_ip_address = '127.0.0.1';
                            $refundModel->created_at = $item->refunded_at;
                            $refundModel->updated_at = $refundModel->created_at;
                        }
                    }
                } else {
                    $chargeModel->home_currency = $chargeModel->currency;
                    $chargeModel->home_currency_amount = $chargeModel->amount;
                    $chargeModel->payment_provider = 'hitpay';
                    $chargeModel->payment_provider_account_id = $chargeModel->business_id;
                    $chargeModel->payment_provider_charge_id = $chargeModel->id;
                    $chargeModel->payment_provider_charge_type = 'business_charge';
                    $chargeModel->fixed_fee = 0;
                    $chargeModel->discount_fee_rate = 0;
                    $chargeModel->discount_fee = 0;
                }

                if ($chargeModel->status === 'succeeded') {
                    $chargeModel->closed_at = $item->completed_at;
                } elseif ($chargeModel->status === 'refunded' || $chargeModel->status === 'void') {
                    $chargeModel->closed_at = $item->refunded_at;
                }

                $this->newConnection->table('business_charges')->insert((array) $chargeModel);

                if (isset($transferModel)) {
                    $this->newConnection->table('business_transfers')->insert((array) $transferModel);
                }

                if (isset($transferReversalModel)) {
                    $this->newConnection->table('business_transfers')->insert((array) $transferReversalModel);
                }

                if (isset($refundModel)) {
                    $this->newConnection->table('business_refunds')->insert((array) $refundModel);
                }

                $receiptRecipientQuery = $this->oldConnection->table('receipt_recipients');
                $receiptRecipientQuery = $receiptRecipientQuery->where('transaction_id', $item->id);
                $receiptRecipientQuery->orderBy('sent_at')->each(function (stdClass $item) use (
                    $chargeModel, &$customers
                ) {
                    /** @var \App\Business\ChargeReceiptRecipient $receiptRecipient */
                    $receiptRecipient = new stdClass;
                    $receiptRecipient->business_charge_id = $chargeModel->id;
                    $receiptRecipient->email = $item->email;
                    $receiptRecipient->sent_at = $item->sent_at;

                    $this->newConnection->table('business_charge_receipt_recipients')
                        ->insert((array) $receiptRecipient);

                    if ($customers->where('email', $receiptRecipient->email)->count() === 0) {
                        /** @var \App\Business\Customer $customer */
                        $customer = new stdClass;
                        $customer->id = $this->getNewUuid();
                        $customer->business_id = $chargeModel->business_id;
                        $customer->email = $receiptRecipient->email;
                        $customer->created_at = Date::now()->toDateTimeString();
                        $customer->updated_at = $customer->created_at;

                        try {
                            $this->newConnection->table('business_customers')->insert((array) $customer);
                        } catch (Exception $exception) {
                            // DO NOTHING
                        }

                        $customers->add([
                            'email' => $customer->email,
                        ]);
                    }
                });
            });

            $bar->finish();
            $this->line('');
            $this->line('');
            $this->listStatus('Charges migrated');

            return $item;
        });

        $this->line('');
        $this->line('Total Invalid : '.$this->accountsCollection->count());

        Storage::append($this->filename.'-done.txt', 'done');
    }

    /**
     * @return string
     */
    private function getNewUuid() : string
    {
        return Str::orderedUuid()->toString();
    }

    /**
     * @param string $group
     * @param $file
     *
     * @return \stdClass
     * @throws \Throwable
     */
    private function processImage(string $group, $file)
    {
        $configuration = $this->configurations[$group];
        $imageFile = ImageFacade::make($file);

        // TODO - 2020-02-16
        // We are converting all images into JPEG format. We will need to update this method if we decided to accept and
        // store different format images in future.

        $imageModel = new stdClass;

        $imageModel->group = $group;
        $imageModel->media_type = 'image/jpeg';
        $imageModel->extension = 'jpg';
        // go back have to add business id and product id

        $storageDefaultDisk = Storage::getDefaultDriver();
        $imageModel->disk = $storageDefaultDisk;

        if (Config::get('filesystems.disks.'.$imageModel->disk.'.driver') === 's3') {
            $imageModel->disk .= ':'.Config::get('filesystems.disks.'.$imageModel->disk.'.bucket');
        }

        $filename = str_replace('-', '', Str::orderedUuid()->toString()).'.jpg';
        $destination = Str::plural($group).DIRECTORY_SEPARATOR;
        $otherDimensions = [];
        $storageSize = 0;

        try {
            foreach ($configuration as $imageSize => $pixels) {
                $imageFile = $imageFile ?? ImageFacade::make($file);

                if ($imageSize !== Size::ORIGINAL) {
                    $imageFile->resize($pixels, $pixels, function (ImageConstraint $constraint) {
                        $constraint->aspectRatio();
                    });
                }

                $streamed = $imageFile->stream('jpg');
                $fileSize = strlen($streamed);

                if ($imageSize === Size::ORIGINAL) {
                    $path = $destination.$filename;

                    $imageModel->path = $path;
                    $imageModel->width = $imageFile->getWidth();
                    $imageModel->height = $imageFile->getHeight();
                    $imageModel->file_size = $fileSize;
                } else {
                    $path = $destination.$imageSize.DIRECTORY_SEPARATOR.$filename;

                    $otherDimensions[$imageSize] = [
                        'size' => $imageSize,
                        'path' => $path,
                        'width' => $imageFile->getWidth(),
                        'height' => $imageFile->getHeight(),
                        'file_size' => $fileSize,
                    ];
                }

                Storage::disk($storageDefaultDisk)->put($path, $streamed);

                $storageSize += $fileSize;

                unset($imageFile, $streamed, $fileSize, $path);
            }

            $imageModel->storage_size = $storageSize;
            $imageModel->other_dimensions = json_encode($otherDimensions);
        } catch (Throwable $exception) {
            $toBeDeleted = collect($otherDimensions)->pluck('path');

            if ($imageModelPath = $imageModel->path) {
                $toBeDeleted->add($imageModelPath);
            }

            Storage::delete($toBeDeleted->toArray());

            throw $exception;
        }

        return $imageModel;
    }

    /**
     * @param string $reason
     * @param stdClass $userBusiness
     */
    private function exceptedBusiness(string $reason, stdClass $userBusiness)
    {
        $this->accountsCollection->add([
            'reason' => $reason,
            'business' => (array) $userBusiness,
        ]);

        $this->error($reason);
    }

    /**
     * @param string $message
     * @param bool $showNumber
     */
    private function listStatus(string $message, bool $showNumber = false)
    {
        if ($showNumber) {
            $this->line(str_pad($this->totalUserProcessed, 6, ' ', STR_PAD_LEFT)
                .' / '.$this->totalUserDetected.' - '.$message);
        } else {
            $this->line(str_repeat(' ', 15).' - '.$message);
        }
    }
}


// todo migrate images
// todo migrate subscriptions
