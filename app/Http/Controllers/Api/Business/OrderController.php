<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Business\Order as OrderModel;
use App\Enumerations\Business\Channel;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Order;
use App\Logics\Business\OrderRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * Relationships can be loaded.
     *
     * @var array
     */
    public static $relationships = [
        'customer',
        'products',
        'charges'
    ];

    /**
     * OrderController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request, BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        $orders = $business->orders();

        if ($with = $request->get('with')) {
            $with = is_array($with) ? $with : explode(',', $with);
            $with = array_map(function ($value) {
                return trim($value);
            }, $with);
            $with = array_unique($with);

            foreach ($with as $index => $relationship) {
                if (in_array($relationship, static::$relationships)) {
                    if ($relationship === 'products') {
                        $orders->with([
                            'products' => function (HasMany $query) {
                                $query->with('image');
                            }
                        ]);
                    } else {
                        $orders->with($relationship);
                    }
                }
            }
        }

        if ($statuses = $request->get('statuses')) {
            $statuses = is_array($statuses) ? $statuses : explode(',', $statuses);
            $statuses = array_map(function ($value) {
                return trim($value);
            }, $statuses);
            $statuses = array_unique($statuses);

            foreach ($statuses as $index => $status) {
                if ($status === 'completed') {
                    $statuses[] = OrderStatus::COMPLETED;
                } elseif ($status === 'pending') {
                    $statuses[] = OrderStatus::REQUIRES_BUSINESS_ACTION;
                } elseif ($status === 'sent') {
                    $statuses[] = OrderStatus::REQUIRES_CUSTOMER_ACTION;
                } elseif ($status === 'draft') {
                    $statuses[] = OrderStatus::DRAFT;
                } elseif ($status === 'expired') {
                    $statuses[] = OrderStatus::EXPIRED;
                } elseif ($status === 'canceled') {
                    $statuses[] = OrderStatus::CANCELED;
                }
            }
        }

        if (!is_array($statuses) || count($statuses) <= 0) {
            $statuses = [
                OrderStatus::CANCELED,
                OrderStatus::COMPLETED,
                OrderStatus::DRAFT,
                OrderStatus::EXPIRED,
                OrderStatus::REQUIRES_BUSINESS_ACTION,
            ];
        }

        $orders->whereIn('status', $statuses);

        $keywords = $request->get('keywords');

        if (strlen($keywords) != 0) {
            if (is_numeric($keywords)) {
                $orders->where('amount', 'LIKE', '%' . $keywords . '%');
            } elseif(strtotime($keywords)) {
                $orders->where('created_at', 'LIKE', '%' . $keywords . '%');
            } elseif(Str::isUuid($keywords)) {
                $orders->where('id', $keywords);
            } else {
                $keywords = explode(' ', $request->get('keywords'));
                $keywords = array_map(function ($value) {
                    return trim($value);
                }, $keywords);
                $keywords = array_filter($keywords);
                $keywords = array_unique($keywords);
                $orders->where(function (Builder $query) use ($keywords) {
                    $i = 0;
                    foreach ($keywords as $keyword) {
                        $query->orWhere($query->qualifyColumn('remark'), 'LIKE', '%' . $keyword . '%');
                        $query->orWhere($query->qualifyColumn('customer_name'), 'LIKE', '%' . $keyword . '%');
                        if ($i++ === 2) {
                            break;
                        }
                    }
                });
            }
        }

        if ($request->get('dateFrom') != '') {
            $dateFrom = Date::parse($request->get('dateFrom'));
            $orders->whereDate('created_at', '>=', $dateFrom->startOfDay()->toDateTimeString());
        }

        if ($request->get('dateTo') != '') {
            $dateTo = Date::parse($request->get('dateTo'));
            $orders->whereDate('created_at', '<=', $dateTo->endOfDay()->toDateTimeString());
        }

        $orders->orderByDesc('id');

        $perPage = $request->get('perPage', 100);

        return Order::collection($orders->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business\Order
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function store(Request $request, BusinessModel $business)
    {
        Gate::inspect('operate', $business)->authorize();

        $request->merge([
            'customer_pickup' => true,
        ]);

        $order = OrderRepository::store($request, $business, Channel::POINT_OF_SALE);

        $order->load(static::$relationships);

        return new Order($order);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Business $business
     * @param \App\Business\Order $order
     *
     * @return \App\Http\Resources\Business\Order
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(BusinessModel $business, OrderModel $order)
    {
        Gate::inspect('view', $business)->authorize();

        $order->load(static::$relationships);
        $order->load([
            'charges',
        ]);

        return new Order($order);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Order $order
     *
     * @return \App\Http\Resources\Business\Order
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function update(Request $request, BusinessModel $business, OrderModel $order)
    {
        Gate::inspect('operate', $business)->authorize();

        $order = OrderRepository::update($request, $business, $order);

        $order->load(static::$relationships);

        return new Order($order);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Order $order
     * @param string $newStatus
     *
     * @return \App\Http\Resources\Business\Order
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateMessageOrStatus(
        Request $request, BusinessModel $business, OrderModel $order, string $newStatus
    ) {
        Gate::inspect('operate', $business)->authorize();

        $data = $this->validate($request, [
            'message' => 'nullable|string|max:4096',
        ]);

        $messages = $order->messages;
        $oldStatus = $order->status;

        $record = microtime(true);

        if ($order->status === OrderStatus::COMPLETED && strtolower($newStatus) === OrderStatus::REQUIRES_BUSINESS_ACTION) {
            $order->status = OrderStatus::REQUIRES_BUSINESS_ACTION;
            $order->closed_at = null;
            $message['status_changed'] = $order->status;
        } elseif ($order->status === OrderStatus::REQUIRES_BUSINESS_ACTION && strtolower($newStatus) === OrderStatus::COMPLETED) {
            $order->status = OrderStatus::COMPLETED;
            $order->closed_at = Date::now();
            $message['status_changed'] = $order->status;
        } else {
            App::abort(404);
        }

        if (isset($data['message'])) {
            if ($oldStatus === OrderStatus::REQUIRES_BUSINESS_ACTION && strtolower($newStatus) === OrderStatus::COMPLETED) {
                $message['shipping_details'] = $data['message'];
            } else {
                $message['plain_message'] = $data['message'];
            }
        }

        $messages[$record] = $message;
        $order->messages = $messages;
        $order->save();
        $order->notifyAboutStatusChanged($data['message'] ?? '', $order->status === OrderStatus::COMPLETED);
        Artisan::queue('sync:hitpay-order-to-ecommerce --order_id=' . $order->id);

        $order->load(static::$relationships);

        return new Order($order);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Business $business
     * @param \App\Business\Order $order
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function destroy(BusinessModel $business, OrderModel $order)
    {
        Gate::inspect('operate', $business)->authorize();

        OrderRepository::delete($business, $order);

        return Response::json([], 204);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Order $order
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function send(Request $request, BusinessModel $business, OrderModel $order)
    {
        Gate::inspect('operate', $business)->authorize();

        if ($order->isDraft()) {
            $order->checkout(false, false, true);
        }

        if (!in_array($order->status, [
            OrderStatus::COMPLETED,
            OrderStatus::REQUIRES_CUSTOMER_ACTION,
        ])) {
            App::abort(403, 'You can\'t send link with status "'.$order->status.'".');
        }

        if ($order->customer_email) { // with customer email, of course customer id is set.
            $nullable = 'nullable';
        }

        $data = $this->validate($request, [
            'customer_id' => [
                $nullable ?? 'required_without:email',
                Rule::exists('business_customers', 'id')->where('business_id', $business->getKey()),
            ],
            'email' => [
                $nullable ?? 'required_without:customer_id',
                'email',
            ],
        ]);

        // you can't send an order which doesn't contain product.

        // will send to the customer on product only

        // todo change status to requires_customer_action
        // todo send email
        // todo record who received email
        // todo create account for $data['email'] if applicable

        return Response::json([
            'success' => true,
        ]);
    }
}
