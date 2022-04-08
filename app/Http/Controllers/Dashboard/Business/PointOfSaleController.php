<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Actions\Business\Stripe\Charge\PaymentIntent\AttachPaymentMethod;
use App\Actions\Business\Stripe\Charge\PaymentIntent\Capture;
use App\Actions\Business\Stripe\Charge\PaymentIntent\Confirm;
use App\Actions\Business\Stripe\Charge\PaymentIntent\Create;
use App\Actions\Business\Stripe\Charge\Source;
use App\Business;
use App\Business\Charge;
use App\Business\Customer;
use App\Business\Order;
use App\Business\OrderedProduct;
use App\Business\Product;
use App\Enumerations\Business\Channel;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\OrderStatus;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\CurrencyCode;
use App\Enumerations\PaymentProvider;
use App\Exceptions\HitPayLogicException;
use App\Helpers\PointOfSale;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Charge as ChargeResource;
use App\Http\Resources\Business\Link as LinkResource;
use App\Http\Resources\Business\PaymentIntent as PaymentIntentResource;
use App\Http\Resources\Business\Product as ProductResource;
use App\Logics\Business\OrderRepository;
use App\Manager\BusinessManagerInterface;
use App\Manager\PaymentRequestManagerInterface;
use Exception;
use HitPay\PayNow\Generator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Stripe\Exception\CardException;

class PointOfSaleController extends Controller
{
    /**
     * PointOfSaleController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show point of sale page.
     *
     * @param Business $business
     * @param BusinessManagerInterface $businessManager
     * @return \Illuminate\Http\Response
     * @throws AuthorizationException
     */
    public function showHomepage(
        Business $business,
        BusinessManagerInterface $businessManager
    )
    {
        Gate::inspect('operate', $business)->authorize();

        $provider = $business->paymentProviders()->where('payment_provider', $business->payment_provider)->first();

        $business->load('stripeTerminalLocations');

        $tax_settings = $business->tax_settings->toArray();

        // check in vue if no service provider than he can only log cash
        $featured_products = [];
        $products = $business->products()
                            ->whereNotNull('published_at')
                            ->orderByDesc('is_pinned')
                            ->orderByDesc('updated_at')
                            ->limit(100)
                            ->get();

        $categories = [];
        $featured_products_attrs = [];
        foreach ($products as $product) {
            $featured_products_attrs['image'][] = $product->display('image');
            $featured_products_attrs['price'][] = $product->display('price');
            $featured_products_attrs['available'][] = $product->isAvailable();

            $productObj = $this->getProductObject($product, $business);
            array_push($featured_products, $productObj);

            if($product->business_product_category_id) {
                foreach ($product->business_product_category_id as $product_category) {
                    if(!in_array($product_category, $categories)){
                        array_push($categories, $product_category);
                    }
                }
            }
        }

        $stripePublishableKey = $businessManager->getStripePublishableKey($business);

        return Response::view('dashboard.business.point-of-sale', compact(
            'business', 'provider',
            'tax_settings','categories', 'featured_products',
            'featured_products_attrs',
            'stripePublishableKey'
        ));
    }

    /**
     * @param Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getConnectionToken(Business $business)
    {
        $provider = $business->paymentProviders()->where('payment_provider', $business->payment_provider)->first();

        if (!$provider) {
            // should fail la.
        }

        $locations = $business->stripeTerminalLocations;

        $location = $locations->first();
        // Set your secret key. Remember to switch to your live secret key in production!
        // See your keys here: https://dashboard.stripe.com/account/apikeys
        \Stripe\Stripe::setApiKey(Config::get('services.stripe.sg.secret'));

        // In a new endpoint on your server, create a ConnectionToken and return the
        // `secret` to your app. The SDK needs the `secret` to connect to a reader.
        $token = \Stripe\Terminal\ConnectionToken::create([
            'location' => $location->stripe_terminal_location_id,
        ]);

        return Response::json([
            'secret' => $token->secret,
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function createCharge(
        Request $request, Business $business,
        BusinessManagerInterface $businessManager
    )
    {
        Gate::inspect('operate', $business)->authorize();

        $currencies = [
            $business->currency,
        ];

        $data = $this->validate($request, [
            'customer_id' => [
                'nullable',
                Rule::exists('business_customers', 'id')->where('business_id', $business->getKey()),
            ],
            'currency' => [
                'required',
                'string',
                Rule::in($currencies),
            ],
            'amount' => [
                'required',
                'numeric',
                'between:0.01,' . PointOfSale::MAX_AMOUNT
            ],
            'remark' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $charge = new Charge;

        $charge->channel = Channel::POINT_OF_SALE;

        if (!empty($data['customer_id'])) {
            $charge->setCustomer($business->customers()->findOrFail($data['customer_id']), true);
        }

        $charge->currency = $data['currency'];
        $charge->remark = $data['remark'] ?? null;
        $charge->amount = getRealAmountForCurrency($charge->currency, $data['amount'], function (string $currency) {
            throw new HitPayLogicException(sprintf('The currency [%s] is invalid.', $currency));
        });

        $charge->status = ChargeStatus::REQUIRES_PAYMENT_METHOD;

        DB::transaction(function () use ($business, $charge) {
            $business->charges()->save($charge);
        });

        $paymentMethods[] = 'cash';

        $businessPaymentMethodAvailables = $businessManager->getByBusinessAvailablePaymentMethods(
            $business,
            $charge->currency,
            true
        );

        $paymentMethods = array_merge($paymentMethods, array_keys($businessPaymentMethodAvailables));

        if ($business->stripeTerminals()->count()) {
            // $businessPaymentProvider = Business\PaymentProvider::where('payment_method', PaymentProvider::STRIPE_SINGAPORE)
            //     ->first();
            //
            // if ($businessPaymentProvider) {
                $paymentMethods = array_merge($paymentMethods, ['card_present']);
            // }
        }

        $charge = $charge->refresh();

        return Response::json([
            'charge_id' => $charge->getKey(),
            'charge' => new ChargeResource($charge),
            'payment_methods' => $paymentMethods,
        ]);
    }

    /**
     * @param Business $business
     * @param Charge $charge
     *
     * @return ChargeResource
     * @throws AuthorizationException
     */
    public function getCharge(Business $business, Charge $charge)
    {
        Gate::inspect('operate', $business)->authorize();

        return new ChargeResource($charge);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Business $business
     * @param Charge $charge
     * @param BusinessManagerInterface $businessManager
     * @return \App\Http\Resources\Business\PaymentIntent
     * @throws HitPayLogicException
     * @throws \App\Actions\Exceptions\BadRequest
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function createPaymentIntentForCharge(
        Request $request,
        Business $business,
        Charge $charge,
        BusinessManagerInterface $businessManager
    )
    {
        $paymentMethods[] = 'cash';

        $businessPaymentMethodAvailables = $businessManager->getByBusinessAvailablePaymentMethods(
            $business,
            $charge->currency,
            true
        );

        $paymentMethods = array_merge($paymentMethods, array_keys($businessPaymentMethodAvailables));

        if ($business->stripeTerminals()->count()) {
            // $businessPaymentProvider = Business\PaymentProvider::where('payment_method', PaymentProvider::STRIPE_SINGAPORE)
            //     ->first();
            //
            // if ($businessPaymentProvider) {
                $paymentMethods = array_merge($paymentMethods, ['card_present']);
            // }
        }

        $data = $this->validate($request, [
            'method' => [
                'required',
                Rule::in($paymentMethods),
            ],
        ]);

        $providers = $business->paymentProviders()->whereNotNull('payment_provider_account_id')->get();
        $provider = $providers->where('payment_provider', $business->payment_provider)->first();

        // TODO - Throw minimum amount error based on the selected currency and payment provider

        switch ($data['method']) {
            case 'card':
            case 'card_present':
            case 'grabpay':

                $paymentIntent = Create::withBusiness($business)->businessCharge($charge)->data($data)->process();

                return new PaymentIntentResource($paymentIntent);

            case 'alipay':
            case 'wechat':
                $paymentIntent = Source\Create::withBusiness($business)->businessCharge($charge)->data([
                    'method' => $data['method'],
                ])->process();

                return new PaymentIntentResource($paymentIntent);

            case 'paynow_online':
                if ($charge->currency !== CurrencyCode::SGD) {
                    throw new HitPayLogicException();
                }

                $paynow = Generator::new()
                    ->setAmount($charge->amount)
                    ->setExpiryAt(Date::now()->addSeconds(300))
                    ->setMerchantName($business->getName());

                $paymentIntent = DB::transaction(function () use ($business, $provider, $charge, $paynow) {
                    return $charge->paymentIntents()->create([
                        'business_id' => $charge->business_id,
                        'payment_provider' => PaymentProvider::DBS_SINGAPORE,
                        // 'payment_provider_account_id' => $provider->payment_provider_account_id,
                        'payment_provider_object_type' => 'inward_credit_notification',
                        'payment_provider_object_id' => $paynow->getReference(),
                        'payment_provider_method' => 'paynow_online',
                        'currency' => $charge->currency,
                        'amount' => $charge->amount,
                        'status' => 'pending',
                        'data' => [
                            'data' => $paynow->generate(),
                        ],
                        'expires_at' => Date::now()->addMinutes(15),
                    ]);
                });

                return new PaymentIntentResource($paymentIntent);
        }

        throw new Exception('Invalid payment method requested.');
    }

    /**
     * @param Business $business
     * @param string $paymentIntentId
     *
     * @return \App\Http\Resources\Business\PaymentIntent
     * @throws AuthorizationException
     */
    public function getPaymentIntent(Business $business, string $paymentIntentId)
    {
        Gate::inspect('operate', $business)->authorize();

        $paymentIntent = $business->paymentIntents()->findOrFail($paymentIntentId);

        $paymentIntent->load('charge');

        return new PaymentIntentResource($paymentIntent);
    }

    /**
     * @param Business $business
     * @param string $paymentIntentId
     *
     * @return \App\Http\Resources\Business\PaymentIntent|\Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws AuthorizationException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function capturePaymentIntent(Business $business, string $paymentIntentId)
    {
        Gate::inspect('operate', $business)->authorize();

        $paymentIntentModel = $business->paymentIntents()->findOrFail($paymentIntentId);

        $paymentIntentModel = Capture::withBusinessPaymentIntent($paymentIntentModel)->process();

        return Response::json($paymentIntentModel->data['stripe']['payment_intent']);
    }

    public function confirmPaymentIntent(Request $request, Business $business, string $paymentIntentId)
    {
        Gate::inspect('operate', $business)->authorize();

        $paymentIntent = $business->paymentIntents()->findOrFail($paymentIntentId);

        try {
            if ($request->has('payment_method_id')) {
                $paymentIntent = AttachPaymentMethod::withBusinessPaymentIntent($paymentIntent)->data([
                    'payment_method' => $request->input('payment_method_id'),
                ])->process();
            } else {
                $paymentIntent = Confirm::withBusinessPaymentIntent($paymentIntent)->process();
            }
        } catch (CardException $exception) {
            return Response::json([
                'error' => $exception->getDeclineCode(),
                'error_message' => $exception->getMessage(),
            ], 400);
        }

        return new PaymentIntentResource($paymentIntent);
    }

    /**
     * @param Business $business
     * @param Charge $charge
     *
     * @throws AuthorizationException
     */
    public function cancelCharge(Business $business, Charge $charge)
    {
        Gate::inspect('operate', $business)->authorize();

        if ($charge->status === ChargeStatus::REQUIRES_PAYMENT_METHOD) {
            $charge->update([
                'status' => ChargeStatus::CANCELED,
                'closed_at' => $charge->freshTimestamp(),
            ]);
        }
    }

    /**
     * @param Business $business
     * @param Charge $charge
     *
     * @return ChargeResource
     * @throws AuthorizationException
     * @throws \Throwable
     */
    public function logCash(Business $business, Charge $charge)
    {
        Gate::inspect('operate', $business)->authorize();

        $charge->home_currency = $charge->currency;
        $charge->home_currency_amount = $charge->amount;
        $charge->payment_provider = 'hitpay';
        $charge->payment_provider_charge_method = 'cash';
        $charge->status = ChargeStatus::SUCCEEDED;
        $charge->closed_at = $charge->freshTimestamp();

        $target = $charge->target;

        if ($target instanceof Order) {
            $target->status = OrderStatus::COMPLETED;
            $target->closed_at = $target->freshTimestamp();
        }

        DB::transaction(function () use ($charge, $target) {
            $charge->save();

            if ($target instanceof Order) {
                $target->save();
                $target->updateProductsQuantities();
                $target->notifyAboutNewOrder();
            }
        });

        return new ChargeResource($charge);
    }

    /**
     * @param Request $request
     * @param Business $business
     * @param Charge $charge
     * @param PaymentRequestManagerInterface $paymentRequestManager
     * @param BusinessManagerInterface $businessManager
     * @return LinkResource
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function logLink(
        Request $request,
        Business $business,
        Charge $charge,
        PaymentRequestManagerInterface $paymentRequestManager,
        BusinessManagerInterface $businessManager
    ) {
        Gate::inspect('operate', $business)->authorize();

        $requestData = $this->validate($request, [
            'allow_repeated_payments' => [
                'nullable',
                Rule::in([true, false]),
            ],
        ]);

        $apiKey         = $business->apiKeys()->first();
        $businessApiKey = $apiKey->api_key;

        $paymentMethods = $businessManager->getByBusinessAvailablePaymentMethods(
            $business,
            $charge->currency
        );

        $data = [
            'email' => null,
            'redirect_url' => null,
            'webhook' => null,
            'currency' => $charge->currency,
            'reference_number' => null,
            'purpose' => $charge->remark,
            'amount' => getReadableAmountByCurrency($charge->currency, $charge->amount),
            'channel' => PluginProvider::LINK,
            'send_email' => true,
            'allow_repeated_payments' => $requestData['allow_repeated_payments'] ?? false,
        ];

        $paymentRequest = $paymentRequestManager->create(
            $data,
            $businessApiKey,
            array_keys($paymentMethods),
            $platform ?? null
        );

        return new LinkResource($paymentRequest);
    }

    public function createOrderResponse(Order $order)
    {
        $order->load('products');

        if ($order->status === OrderStatus::REQUIRES_POINT_OF_SALES_ACTION) {
            $order->checkout('', true, true);
        }

        $data['id'] = $order->getKey();

        if ($order->business_customer_id) {
            $data['customer'] = [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone_number' => $order->customer_phone_number,
                'address' => implode(', ', array_filter([
                    $order->customer_street,
                    $order->customer_city,
                    $order->customer_state,
                    $order->customer_postal_code,
                    $order->customer_country,
                ])),
            ];
        } else {
            $data['customer'] = null;
        }

        $currency = $order->currency;
        $tax_setting = Business\TaxSetting::find($order->tax_setting_id);

        $data['currency'] = $currency;
        $data['tax_amount'] = getReadableAmountByCurrency($currency, $order->line_item_tax_amount);
        $data['discount_name'] = $order->automatic_discount_reason;
        $data['discount_amount'] = getReadableAmountByCurrency($currency, $order->additional_discount_amount);
        $data['tax_setting_name'] = $tax_setting ? $tax_setting->name : '';
        $data['tax_setting_amount'] = $tax_setting ? $tax_setting->rate : '';
        $data['total_amount'] = getReadableAmountByCurrency($currency, $order->amount);
        $data['actual_tax_amount'] = $order->line_item_tax_amount;
        $data['actual_discount_amount'] = $order->additional_discount_amount;
        $data['actual_total_amount'] = $order->amount;
        $data['products'] = [];

        foreach ($order->products->sortBy('created_at') as $product) {
            $data['products'][] = [
                'id' => $product->getKey(),
                'name' => $product->name,
                'image' => $product->image ? $product->image->getUrl() : '',
                'variation_key_1' => $product->variation_key_1,
                'variation_value_1' => $product->variation_value_1,
                'variation_key_2' => $product->variation_key_2,
                'variation_value_2' => $product->variation_value_2,
                'variation_key_3' => $product->variation_key_3,
                'variation_value_3' => $product->variation_value_3,
                'quantity' => $product->quantity,
                'unit_price' => getReadableAmountByCurrency($currency, $product->unit_price),
                'total_price' => getReadableAmountByCurrency($currency, $product->price),
                'actual_unit_price' => $product->unit_price,
                'actual_total_price' => $product->price,
            ];
        }

        return Response::json($data);
    }

    /**
     * @param Business $business
     * @param Order $order
     * @param BusinessManagerInterface $businessManager
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     */
    public function checkoutOrder(
        Business $business,
        Order $order,
        BusinessManagerInterface $businessManager)
    {
        Gate::inspect('operate', $business)->authorize();

        if ($order->requiresPointOfSaleAction()) {
            $order->checkout();
        }

        if (!$order->requiresPaymentMethod()) {
            App::abort(403, 'You can\'t charge a non-requires payment method order.');
        }

        $charge = new Charge;

        $charge->channel = $order->channel;

        $charge->business_customer_id = $order->business_customer_id;
        $charge->customer_name = $order->customer_name;
        $charge->customer_email = $order->customer_email;
        $charge->customer_phone_number = $order->customer_phone_number;
        $charge->customer_street = $order->customer_street;
        $charge->customer_city = $order->customer_city;
        $charge->customer_state = $order->customer_state;
        $charge->customer_postal_code = $order->customer_postal_code;
        $charge->customer_country = $order->customer_country;
        $charge->currency = $order->currency;
        $charge->remark = Str::limit($order->products->pluck('name')->implode(', '));
        $charge->amount = $order->amount;
        $charge->status = ChargeStatus::REQUIRES_PAYMENT_METHOD;

        $charge->target()->associate($order);

        DB::transaction(function () use ($business, $charge) {
            $business->charges()->save($charge);
        });

        $paymentMethods[] = 'cash';

        $businessPaymentMethodAvailables = $businessManager->getByBusinessAvailablePaymentMethods(
            $business,
            $charge->currency,
            true
        );

        $paymentMethods = array_merge($paymentMethods, array_keys($businessPaymentMethodAvailables));

        if ($business->stripeTerminals()->count()) {
            // $businessPaymentProvider = PaymentProvider::where('payment_method', PaymentProvider::STRIPE_SINGAPORE)->first();
            //
            // if ($businessPaymentProvider) {
                $paymentMethods = array_merge($paymentMethods, ['card_present']);
            // }
        }

        $charge = $charge->refresh();

        return Response::json([
            'charge_id' => $charge->getKey(),
            'charge' => new ChargeResource($charge),
            'payment_methods' => $paymentMethods,
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function createOrder(Request $request, Business $business)
    {
        Gate::inspect('operate', $business)->authorize();

        $order = OrderRepository::store($request, $business, Channel::POINT_OF_SALE,
            OrderStatus::REQUIRES_POINT_OF_SALES_ACTION);

        return $this->createOrderResponse($order);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Business $business
     * @param \App\Business\Order $order
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function addCustomerToOrder(Request $request, Business $business, Order $order)
    {
        Gate::inspect('operate', $business)->authorize();

        $data = $this->validate($request, [
            'customer_id' => [
                'nullable',
                Rule::exists('business_customers', 'id')->where('business_id', $business->getKey()),
            ],
        ]);

        $order->setCustomer(Customer::where('business_id', $business->getKey())->findOrFail($data['customer_id']),
            true
        );

        $order = DB::transaction(function () use ($order) : Order {
            $order->save();

            return $order;
        }, 3);

        return $this->createOrderResponse($order);
    }

    /**
     * @param Business $business
     * @param \App\Business\Order $order
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     * @throws \Throwable
     */
    public function removeCustomerFromOrder(Business $business, Order $order)
    {
        Gate::inspect('operate', $business)->authorize();

        $order->setCustomer(null, $order->status === OrderStatus::REQUIRES_POINT_OF_SALES_ACTION);

        $order = DB::transaction(function () use ($order) : Order {
            $order->save();

            return $order;
        }, 3);

        return $this->createOrderResponse($order);
    }

    public function setDiscount(Request $request, Business $business, Order $order)
    {
        Gate::inspect('operate', $business)->authorize();

        $data = $this->validate($request, [
            'name' => 'nullable|string|max:32',
            'amount' => 'required|decimal:0,2',
        ]);

        $order->automatic_discount_reason = $data['name'] ?? 'Discount';
        $order->additional_discount_amount = getRealAmountForCurrency($order->currency, $data['amount']);
        $order->save();

        return $this->createOrderResponse($order);
    }

    public function setTaxSetting(Request $request, Business $business, Order $order)
    {
        Gate::inspect('operate', $business)->authorize();

        $data = $this->validate($request, [
            'tax_setting_id' => 'required|string',
        ]);

        $order->tax_setting_id = $data['tax_setting_id'];
        $order->save();

        return $this->createOrderResponse($order);
    }

    public function removeDiscountFromOrder(Business $business, Order $order)
    {
        // todo
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Business $business
     * @param \App\Business\Order $order
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function addProductToOrder(Request $request, Business $business, Order $order)
    {
        Gate::inspect('operate', $business)->authorize();

        $request->merge([
            'skip_quantity_check' => true,
        ]);

        $order = OrderRepository::addProduct($request, $business, $order, false);

        return $this->createOrderResponse($order);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Business $business
     * @param \App\Business\Order $order
     * @param \App\Business\OrderedProduct $product
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function updateProductInOrder(Request $request, Business $business, Order $order, OrderedProduct $product)
    {
        Gate::inspect('operate', $business)->authorize();

        $request->merge([
            'skip_quantity_check' => true,
        ]);

        $order = OrderRepository::updateProduct($request, $business, $order, $product);

        return $this->createOrderResponse($order);
    }

    /**
     * @param Business $business
     * @param \App\Business\Order $order
     * @param \App\Business\OrderedProduct $product
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     * @throws \Throwable
     */
    public function deleteProductFromOrder(Business $business, Order $order, OrderedProduct $product)
    {
        Gate::inspect('operate', $business)->authorize();

        DB::transaction(function () use ($product) : void {
            $product->delete();
        }, 3);

        return $this->createOrderResponse($order);
    }

    public function deleteOrder(Business $business, Order $order)
    {
        // todo
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Business $business
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function searchProduct(Request $request, Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $products = $business->products();

        $keywords = $request->get('keywords');
        $keywords = is_array($keywords) ? $keywords : explode(' ', $keywords);
        $keywords = array_map(function ($value) {
            return trim($value);
        }, $keywords);
        $keywords = array_filter($keywords);
        $keywords = array_unique($keywords);

        if (count($keywords)) {
            foreach ($keywords as $keyword) {
                $products->where('name', 'like', '%'.$keyword.'%');
            }
        } else {
            $products->whereNull('id');
        }

        $products->with('variations');

        return ProductResource::collection($products->limit(8)->get());
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     */
    public function searchCustomer(Request $request, Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $customers = $business->customers();

        $keywords = $request->get('keywords');
        $keywords = is_array($keywords) ? $keywords : explode(' ', $keywords);
        $keywords = array_map(function ($value) {
            return trim($value);
        }, $keywords);
        $keywords = array_filter($keywords);
        $keywords = array_unique($keywords);

        if (count($keywords)) {
            $customers->where(function (Builder $query) use ($keywords) {
                $query->where(function (Builder $query) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $query->where('name', 'like', '%'.$keyword.'%');
                    }
                })->orWhere(function (Builder $query) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $query->where('email', 'like', '%'.$keyword.'%');
                    }
                });
            });
        } else {
            $customers->whereNull('id');
        }

        $customers = $customers->limit(8)->get();

        $data = [];

        foreach ($customers as $customer) {
            $data[] = [
                'id' => $customer->getKey(),
                'name' => $customer->name,
                'email' => $customer->email,
                'phone_number' => $customer->phone_number,
                'address' => implode(', ', array_filter([
                    $customer->street,
                    $customer->city,
                    $customer->state,
                    $customer->postal_code,
                    $customer->country,
                ])),
            ];
        }

        return Response::json($data);
    }

    public function showAlipayStatus(Business $business, Charge $charge)
    {
        dd('ok');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     */
    public function searchDiscounts(Request $request, Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $discounts = $business->discounts();

        $keywords = $request->get('keywords');
        $keywords = is_array($keywords) ? $keywords : explode(' ', $keywords);
        $keywords = array_map(function ($value) {
            return trim($value);
        }, $keywords);
        $keywords = array_filter($keywords);
        $keywords = array_unique($keywords);

        if (count($keywords)) {
            $discounts->where(function (Builder $query) use ($keywords) {
                $query->where(function (Builder $query) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $query->where('name', 'like', '%'.$keyword.'%');
                    }
                });
            });
        } else {
            $discounts->whereNull('id');
        }

        $discounts = $discounts->limit(8)->get();

        $data = [];

        foreach ($discounts as $discount) {
            $data[] = [
                'id' => $discount->getKey(),
                'name' => $discount->name,
                'percentage' => $discount->percentage,
                'fixed_amount' =>  $discount->fixed_amount
            ];
        }

        return Response::json($data);
    }

    private function getProductObject(Product $product)
    {
        if (!$product->shortcut_id) {
            $shortcut = $product->shortcut()->create([
                'route_name' => 'shop.product',
                'parameters' => [
                    'business' => $product->business_id,
                    'product_id' => $product->getKey(),
                ],
            ]);

            $product->shortcut_id = $shortcut->getKey();
            $product->save();
        }
        $data['id'] = $product->id;
        $data['name'] = $product->name;
        $data['description'] = $product->description;
        $data['currency'] = $product->currency;
        $data['price'] = $product->price;
        $data['stock_keeping_unit'] = $product->stock_keeping_unit;
        $data['categories'] = $product->business_product_category_id;
        $data['readable_price'] = $product->readable_price;
        $data['is_manageable'] = (!is_null($product->quantity) && $product->quantity > 0) ? true : false;
        $data['is_published'] = $product->published_at instanceof Carbon;
        $data['has_variations'] = $product->variations_count > 1;
        $data['variations_count'] = $product->variations_count;
        $data['checkout_url'] = $product->shortcut_id
            ? URL::route('shortcut', $product->shortcut_id)
            : URL::route('shop.product', [
                $product->business_id,
                $product->getKey(),
            ]);
        if ($product->variations_count > 1) {
            $data['variation_types'] = array_filter([
                $product->variation_key_1,
                $product->variation_key_2,
                $product->variation_key_3,
            ]);
        } elseif ($data['is_manageable']) {
            $data['quantity'] = $product->variations[0]->quantity ?? 0;
            $data['quantity_alert_level'] = $product->variations[0]->quantity_alert_level ?? null;
        }

        $data['variations'] = [];

        foreach ($product->variations as $variation) {
            $variationData = [
                'id' => $variation->id,
                'description' => $variation->description,
                'values' => [
                    [
                        'key' => $product->variation_key_1,
                        'value' => $variation->variation_value_1,
                    ],
                    [
                        'key' => $product->variation_key_2,
                        'value' => $variation->variation_value_2,
                    ],
                    [
                        'key' => $product->variation_key_3,
                        'value' => $variation->variation_value_3,
                    ],
                ],
                'price' => getReadableAmountByCurrency($product->currency, $variation->price),
            ];

            if ($data['is_manageable']) {
                $variationData['quantity'] = $variation->quantity;
                $variationData['quantity_alert_level'] = $variation->quantity_alert_level;
            }

            $data['variations'][] = $variationData;
        }

        if ($product->relationLoaded('images')) {
            foreach ($product->images as $image) {
                $data['image'][] = [
                    'id' => $image->getKey(),
                    'url' => $image->getUrl(),
                ];
            }
        }

        $data['is_pinned'] = $product->is_pinned;
        $data['created_at'] = $product->created_at->toAtomString();
        $data['updated_at'] = $product->updated_at->toAtomString();

        return $data;
    }

    public function getProductWithCategory(Business $business, Request $request)
    {
        $category_id = $request->category_id;
        $products = $business->products()
                            ->whereNotNull('published_at')
                            ->orderByDesc('is_pinned')
                            ->orderByDesc('updated_at')
                            ->get();

        if ($category_id != 'home') {
            foreach ($products as $key => $product) {
                if ($product->business_product_category_id) {
                    $flag = false;
                    foreach ($product->business_product_category_id as $product_category) {
                        if ($product_category->id == $category_id) {
                            $flag = true;
                            break;
                        }
                    }
                    if (!$flag) $products->forget($key);
                } else $products->forget($key);
            }
        }

        $featured_product_attrs = [];
        $featured_products = [];

        foreach ($products as $product) {
            $featured_product_attrs['image'][] = $product->display('image');
            $featured_product_attrs['price'][] = $product->display('price');
            $featured_product_attrs['available'][] = $product->isAvailable();

            $productObj = $this->getProductObject($product, $business);
            array_push($featured_products, $productObj);
        }

        return Response::json([
            'featured_products' => $featured_products,
            'featured_product_attrs' => $featured_product_attrs
            ]);
    }
}
