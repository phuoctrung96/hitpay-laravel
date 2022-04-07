<?php

namespace App\Http\Controllers\MigratedApi;

use App\Business;
use App\Business\Order;
use App\Enumerations\Business\Channel;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\OrderStatus;
use Exception;
use HitPay\Stripe\Charge as StripeCharge;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Stripe\Source;

class OrderController extends Controller
{
    /**
     * OrderController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Get order list.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \ReflectionException
     */
    public function index(Request $request)
    {
        $business = $this->getBusiness($request);

        $order = $business->orders();

        $status = $request->get('status');

        if ($status === 'completed') {
            $status = OrderStatus::COMPLETED;
        } elseif ($status === 'pending' || $status === 'paid') {
            $status = OrderStatus::REQUIRES_BUSINESS_ACTION;
        } elseif ($status === 'cancelled') {
            $status = OrderStatus::CANCELED;
        }

        $order->where('status', $status);

        if ($request->get('withProduct')) {
            $order->with('products');
        }

        if ($request->get('withTransaction')) {
            $order->with('charges');
        }

        if ($status === OrderStatus::COMPLETED) {
            $order->orderByDesc('closed_at');
        } else {
            $order->orderByDesc('created_at');
        }

        $order = $order->paginate();

        return Response::json($this->getOrderListObject($order, $business));
    }

    /**
     * Show a single product.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id
     *
     * @return mixed
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function show(Request $request, string $id)
    {
        $business = $this->getBusiness($request);

        $order = $business->orders()->with('products', 'charges')->findOrFail($id);

        return Response::json($this->getOrderObject($order, $business));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @param string $newStatus
     *
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, string $id, string $newStatus)
    {
        $business = $this->getBusiness($request);
        $order = $business->orders()->findOrFail($id);

        $data = $this->validate($request, [
            'message' => 'nullable|string|max:4096',
            'remind_buyer' => 'nullable|bool',
        ]);

        $messages = $order->messages;
        $oldStatus = $order->status;

        $record = microtime(true);

        if ($order->status === OrderStatus::COMPLETED && strtolower($newStatus) === 'paid') {
            $order->status = OrderStatus::REQUIRES_BUSINESS_ACTION;
            $order->closed_at = null;
            $message['status_changed'] = $order->status;
        } elseif ($order->status === OrderStatus::REQUIRES_BUSINESS_ACTION && strtolower($newStatus) === 'completed') {
            $order->status = OrderStatus::COMPLETED;
            $order->closed_at = Date::now();
            $message['status_changed'] = $order->status;
        } else {
            App::abort(404);
        }

        if (isset($data['message'])) {
            if ($oldStatus === OrderStatus::REQUIRES_BUSINESS_ACTION
                && strtolower($newStatus) === OrderStatus::COMPLETED) {
                $message['shipping_details'] = $data['message'];
            } else {
                $message['plain_message'] = $data['message'];
            }
        }

        $messages[$record] = $message;
        $order->messages = $messages;
        $order->save();
        $order->notifyAboutStatusChanged($data['message'] ?? '', $order->status === OrderStatus::COMPLETED);

        return Response::json($this->getOrderObject($order, $business));
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function create(Request $request)
    {
        $business = $this->getBusiness($request);

        $business->load('paymentProviders');

        $paymentProvider = $business->paymentProviders->where('payment_provider', $business->payment_provider)->first();

        if (!$paymentProvider instanceof Business\PaymentProvider) {
            App::abort(403, 'You don\'t have payment provider setup. Please setup your stripe account in'
                .' https://dashboard.hit-pay.com');
        }

        $data = $this->validate($request, [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255',
            'method' => 'nullable|string|in:cash,paynow,wechat',
            'remark' => 'nullable|string|max:65536',
            'product' => 'required|array|max:25',
            'product.*.id' => 'required|string',
            'product.*.variation_id' => 'nullable|string',
            'product.*.quantity' => 'required|int|min:1',
            'payment_intent' => 'nullable|bool',
            'source' => 'required_if:payment_intent,1|string',
        ]);

        $stripe = StripeCharge::new($paymentProvider->payment_provider);

        $order = new Order;

        // todo create customer

        $order->customer_name = $data['name'] ?? null;
        $order->customer_email = $data['email'] ?? null;
        $order->customer_pickup = true;
        $order->channel = Channel::POINT_OF_SALE;
        $order->currency = $business->currency;
        $order->remark = $data['remark'] ?? null;
        $order->status = OrderStatus::DRAFT;

        $productsCollection = Collection::make($data['product']);

        $toBeSearchedIds = [];

        $productsCollection->each(function ($item) use (&$toBeSearchedIds) {
            if (array_key_exists('variation_id', $item)) {
                $toBeSearchedIds[] = $item['variation_id'];
            } else {
                $toBeSearchedIds[] = $item['id'];
            }
        });

        $products = $business->productVariations()->with('product')->whereIn('id', $toBeSearchedIds)->get();

        $productsArray = [];

        foreach ($productsCollection as $key => $value) {
            /** @var \App\Business\ProductVariation $variation */
            $variation = $products->where('id', ($value['variation_id'] ?? $value['id']))->first();

            if ($variation->product->isManageable() && $variation->quantity < $value['quantity']) {
                throw ValidationException::withMessages([
                    'products.'.$key.'.quantity' => 'Product '.$variation->product->name.' has insufficient inventory.',
                ]);
            }

            $orderedProduct = new Business\OrderedProduct;

            $orderedProduct->business_product_id = $variation->getKey();
            $orderedProduct->name = $variation->product->name;
            $orderedProduct->description = $variation->description;
            $orderedProduct->variation_key_1 = $variation->product->variation_key_1;
            $orderedProduct->variation_value_1 = $variation->variation_value_1;
            $orderedProduct->variation_key_2 = $variation->product->variation_key_2;
            $orderedProduct->variation_value_2 = $variation->variation_value_3;
            $orderedProduct->variation_key_3 = $variation->product->variation_key_3;
            $orderedProduct->variation_value_3 = $variation->variation_value_3;
            $orderedProduct->quantity = $value['quantity'];
            $orderedProduct->remark = $value['remark'] ?? null;
            $orderedProduct->unit_price = $variation->price;
            $orderedProduct->discount_amount = 0;
            $orderedProduct->price = bcmul($orderedProduct->quantity, $orderedProduct->unit_price)
                - $orderedProduct->discount_amount;

            if ($productTax = $variation->product->tax) {
                $orderedProduct->tax_name = $productTax->name;
                $orderedProduct->tax_rate = (float) $productTax->rate;
            } else {
                $orderedProduct->tax_rate = 0;
            }

            $orderedProduct->tax_amount = $orderedProduct->price
                - (int) bcdiv($orderedProduct->price, (1 + $orderedProduct->tax_rate));

            if ($image = $variation->product->images()->first()) {
                $orderedProduct->business_image_id = $image->getKey();
            }

            $productsArray[] = $orderedProduct;
        }

        if (!array_key_exists('method', $data)) {
            $data['method'] = 'unknown';
        }

        $object = DB::transaction(function () use (
            $business, $order, $productsArray, $paymentProvider, $stripe, $data
        ) {
            $business->orders()->save($order);
            $order->products()->saveMany($productsArray);
            $order->checkout(true);

            $charge = new Business\Charge;

            $charge->channel = $order->channel;
            $charge->business_customer_id = $order->business_customer_id;
            $charge->customer_name = $order->customer_name;
            $charge->customer_email = $order->customer_email;
            $charge->currency = $order->currency;
            $charge->remark = Str::limit($order->products->pluck('name')->implode(', '));
            $charge->amount = $order->amount;

            if ($data['method'] === 'paynow' || $data['method'] === 'cash') {
                $charge->home_currency = $charge->currency;
                $charge->home_currency_amount = $charge->amount;
                $charge->payment_provider = 'hitpay';
                $charge->payment_provider_charge_method = $data['method'];
                $charge->status = ChargeStatus::SUCCEEDED;
                $charge->closed_at = $charge->freshTimestamp();

                $order->status = OrderStatus::COMPLETED;
                $order->closed_at = $order->freshTimestamp();

                $charge->target()->associate($order);
                $business->charges()->save($charge);
                $order->save();
                $order->updateProductsQuantities();
                $order->notifyAboutNewOrder();

                return $order;
            }

            $charge->status = ChargeStatus::REQUIRES_PAYMENT_METHOD;

            if ($data['payment_intent'] ?? false) {
                $stripePaymentIntent = $stripe->createPaymentIntent($paymentProvider->payment_provider_account_id,
                    $charge->currency, $charge->amount, $business->statementDescription(), [
                        'remark' => $charge->remark,
                        'payment_method_types' => [
                            'card',
                        ],
                        'capture_method' => 'automatic',
                    ]);

                try {
                    $charge->target()->associate($order);
                    $business->charges()->save($charge);

                    $metadata = $stripePaymentIntent->metadata->toArray();

                    $metadata['charge_id'] = $charge->getKey();

                    $stripePaymentIntent = $stripe->updatePaymentIntent($stripePaymentIntent->id, $metadata);

                    $charge->paymentIntents()->create([
                        'business_id' => $charge->business_id,
                        'payment_provider' => $paymentProvider->payment_provider,
                        'payment_provider_account_id' => $paymentProvider->payment_provider_account_id,
                        'payment_provider_object_type' => $stripePaymentIntent->object,
                        'payment_provider_object_id' => $stripePaymentIntent->id,
                        'payment_provider_method' => $stripePaymentIntent->type,
                        'currency' => $stripePaymentIntent->currency,
                        'amount' => $stripePaymentIntent->amount,
                        'status' => $stripePaymentIntent->status,
                        'data' => $stripePaymentIntent->toArray(),
                    ]);
                } catch (Exception $exception) {
                    $stripePaymentIntent->cancel();

                    throw $exception;
                }

                return $stripePaymentIntent;
            }

            $stripeSource = $stripe->createSource($data['method'], $charge->currency, $charge->amount,
                $business->statementDescription(), null, $sourceData ?? []);

            $metadata = $stripeSource->metadata->toArray();

            $metadata['charge_id'] = $charge->getKey();

            $stripeSource = $stripe->updateSource($stripeSource->id, $metadata);

            $charge->target()->associate($order);
            $business->charges()->save($charge);

            $charge->paymentIntents()->create([
                'business_id' => $charge->business_id,
                'payment_provider' => $paymentProvider->payment_provider,
                'payment_provider_account_id' => $paymentProvider->payment_provider_account_id,
                'payment_provider_object_type' => $stripeSource->object,
                'payment_provider_object_id' => $stripeSource->id,
                'payment_provider_method' => $stripeSource->type,
                'currency' => $stripeSource->currency,
                'amount' => $stripeSource->amount,
                'status' => $stripeSource->status,
                'data' => $stripeSource->toArray(),
                'expires_at' => Date::createFromTimestamp($stripeSource->created)->addHours(6),
            ]);

            return $stripeSource;
        });

        if ($object instanceof Order && $object->status === OrderStatus::COMPLETED) {
            $object->load([
                'charges' => function (MorphMany $query) {
                    $query->where('status', ChargeStatus::SUCCEEDED);
                },
            ]);

            $object = $object->refresh();

            return $this->getOrderObject($object, $business);
        } elseif ($object instanceof Source) {
            return Response::json([
                'order_id' => $order->id,
                'source' => $object->toArray(),
            ]);
        }

        /** @var \Stripe\PaymentIntent $object */
        return Response::json([
            'order_id' => $order->id,
            'payment_intent' => $object->client_secret,
            'url' => URL::route('migrated-api.order.show', $order->id),
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $id
     *
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function pay(Request $request, string $id)
    {
        $business = $this->getBusiness($request);
        $order = $business->orders()->findOrFail($id);

        $keepTrying = 0;

        do {
            if ($order->status === OrderStatus::REQUIRES_BUSINESS_ACTION
                || $order->status === OrderStatus::COMPLETED) {
                $order->load([
                    'products',
                    'charges' => function (MorphMany $query) {
                        $query->where('status', ChargeStatus::SUCCEEDED);
                    },
                ]);

                return Response::json($this->getOrderObject($order, $business));
            }

            usleep(500000);

            $order = $order->refresh();

            $keepTrying++;
        } while ($keepTrying < 60);

        throw new RuntimeException('Time out and the payment was not capture for order: '.$order->getKey()
            .'. This can be a normal scenario.');
    }

    protected function getOrderObject(Order $order, Business $business)
    {
        $data['id'] = $order->getKey();
        $data['merchant'] = [
            'id' => $business->getKey(),
            'name' => $business->name,
            'display_name' => $business->display_name,
            'country_code' => strtoupper($business->country),
        ];
        $data['buyer'] = [
            'name' => $order->customer_name,
            'email' => $order->customer_email,
        ];
        $data['currency_code'] = strtoupper($order->currency);
        $data['amount'] = getReadableAmountByCurrency($order->currency, $order->amount);
        $data['shipping_amount'] = getReadableAmountByCurrency($order->currency, $order->shipping_amount);
        $data['is_shippable'] = !$order->customer_pickup;
        $data['country_code'] = $order->customer_country ? strtoupper($order->customer_country) : null;
        $data['image_url'] = null;

        if ($order->status === 'requires_business_action') {
            if ($order->customer_pickup) {
                $data['status'] = 'paid';
            } else {
                $data['status'] = 'pending';
            }
        } else {
            $data['status'] = $order->status;
        }

        $data['remark'] = $order->remark;

        if ($order->relationLoaded('products')) {
            foreach ($order->products as $product) {
                $imageUrl = $product->image ? $product->image->getUrl() : null;

                if (!array_key_exists('image_url', $data)) {
                    $data['image_url'] = $imageUrl;
                }

                $data['products'][] = [
                    'id' => $product->getKey(),
                    'type' => 'unknown',
                    'product_id' => $product->business_product_id,
                    'name' => $product->name,
                    'is_shippable' => $data['is_shippable'],
                    'quantity' => $product->quantity,
                    'amount' => getReadableAmountByCurrency($order->currency, $product->price),
                    'shipping_amount' => null,
                    'note' => $product->remark,
                    'image_url' => $imageUrl,
                ];
            }
        }

        if ($order->relationLoaded('charges')) {
            foreach ($order->charges as $charge) {
                if ($charge->status === 'succeeded') {
                    $chargeStatus = 'completed';
                } elseif ($charge->status === 'void') {
                    $chargeStatus = 'cancelled';
                } else {
                    $chargeStatus = $charge->status;
                }

                $data['transaction'][] = [
                    'id' => $charge->getKey(),
                    'platform' => $charge->payment_provider === 'stripe_sg' ? 'stripe' : $charge->payment_provider,
                    'method' => $charge->payment_provider_charge_method === 'card'
                        ? 'payment_card' : $charge->payment_provider_charge_method,
                    'currency_code' => strtoupper($charge->currency),
                    'amount' => getReadableAmountByCurrency($charge->currency, $charge->amount),
                    'status' => $chargeStatus,
                    'remark' => $charge->remark,
                    'extra_data' => [
                        'type' => $charge->payment_provider_charge_method,
                    ],
                ];
            }
        }

        $data['created_at'] = $order->created_at->getTimestamp();
        $data['updated_at'] = $order->updated_at->getTimestamp();

        return $data;
    }

    private function getOrderListObject(LengthAwarePaginator $paginator, Business $business)
    {
        /**
         * @var \Illuminate\Pagination\LengthAwarePaginator $paginator
         */
        $currentPage = $paginator->currentPage();

        $pagination = [];
        $pagination['self'] = $paginator->url($currentPage);
        $pagination['first'] = $paginator->url(1);

        if ($currentPage > 1) {
            $pagination['prev'] = $paginator->url($currentPage - 1);
        }

        $lastPage = $paginator->lastPage();

        if ($currentPage < $lastPage) {
            $pagination['next'] = $paginator->url($currentPage + 1);
        }

        $pagination['last'] = $paginator->url($lastPage);

        $data['meta'] = [
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $currentPage,
                'total_pages' => $lastPage,
                'links' => $pagination,
            ],
        ];

        $data['data'] = [];

        foreach ($paginator->items() as $item) {
            $data['data'][] = $this->getOrderObject($item, $business);
        }

        return $data;
    }
}
