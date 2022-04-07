<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Business\Order;
use App\Enumerations\Business\OrderStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendExportedOrders;
use App\Jobs\SendExportedDeliveryReport;
use App\Enumerations\Business\ChargeStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * OrderController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request, Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $orders = $business->orders()->whereIn('status', [OrderStatus::CANCELED, OrderStatus::COMPLETED, OrderStatus::REQUIRES_BUSINESS_ACTION])->with('products', 'charges');

        $statuses = array(
            'All' => 'all',
            'Completed' => 'completed',
            'Pending' => 'requires_business_action',
            'Canceled' => 'canceled');

        $orders = $orders->orderByDesc('id')->get();

        return Response::view('dashboard.business.order.index', compact('business', 'orders', 'statuses'));
    }

    public function search(Request $request, Business $business){
        Gate::inspect('view', $business)->authorize();
        $keywords = $request->get('keywords');

        $orders = $business->orders()->whereIn('status', [OrderStatus::CANCELED, OrderStatus::COMPLETED, OrderStatus::REQUIRES_BUSINESS_ACTION])->with('products', 'charges');

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

        $orders = $orders->orderByDesc('id')->get();
        return Response::json([
            'orders' => $orders,
        ]);
    }

    public function getOrdersWithFilters(Request $request, Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $orders = $business->orders()->whereIn('status', [OrderStatus::CANCELED, OrderStatus::COMPLETED, OrderStatus::REQUIRES_BUSINESS_ACTION])->with('products','charges');

        $status = $request->get('status');
        $statusLabel = 'All';

        if ($status === 'requires_business_action') {
            $orders->where('status', OrderStatus::REQUIRES_BUSINESS_ACTION);
            $statusLabel = 'Pending';
        } elseif ($status === 'completed') {
            $orders->where('status', OrderStatus::COMPLETED);
            $statusLabel = 'Completed';
        } elseif ($status === 'canceled') {
            $orders->where('status', OrderStatus::CANCELED)->orWhere('status', OrderStatus::EXPIRED);
            $statusLabel = 'Canceled';
        }

        if ($request->get('dateFrom') != '' && $request->get('dateTo') != '') {
            $dateFrom = Date::parse($request->get('dateFrom'));
            $dateTo = Date::parse($request->get('dateTo'));

            if ($dateFrom->gt($dateTo)) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }

            $orders->whereDate('created_at', '>=', $dateFrom->startOfDay()->toDateTimeString());
            $orders->whereDate('created_at', '<=', $dateTo->endOfDay()->toDateTimeString());
        }

        $orders = $orders->orderByDesc('id')->get();

        return Response::json([
            'orders' => $orders,
            'status' => $statusLabel
        ]);

    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function export(Request $request, Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $data = $this->validate($request, [
            'starts_at' => [
                'date_format:Y-m-d',
            ],
            'ends_at' => [
                'date_format:Y-m-d',
            ],
            'docType' => [
                'required'
            ],
            'status' => [
                'required'
            ]
        ]);

        $orders = $business->orders()->with('products');

        $status = $request->get('status');

        if ($status === 'requires_business_action') {
            $orders->where('status', OrderStatus::REQUIRES_BUSINESS_ACTION);
        } elseif ($status === 'requires_customer_action') {
            $orders->where('status', OrderStatus::REQUIRES_CUSTOMER_ACTION);
        } elseif ($status === 'requires_payment_method') {
            $orders->where('status', OrderStatus::REQUIRES_PAYMENT_METHOD);
        } elseif ($status === 'completed') {
            $orders->where('status', OrderStatus::COMPLETED);
        } elseif ($status === 'canceled') {
            $orders->where('status', OrderStatus::CANCELED)->orWhere('status', OrderStatus::EXPIRED);
        }

        $fromDate = Date::parse($data['starts_at']);
        $toDate = Date::parse($data['ends_at']);

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $orders->whereDate('created_at', '>=', $fromDate->startOfDay()->toDateTimeString());
        $orders->whereDate('created_at', '<=', $toDate->endOfDay()->toDateTimeString());
        if ($orders->count() < 1) {
            App::abort(422, 'You don\'t have any order between these date.');
        }

        SendExportedOrders::dispatch($business, [
            'status' => $data['status'],
            'from_date' => $data['starts_at'],
            'to_date' => $data['ends_at'],
            'docType' => $data['docType']
        ]);

        return Response::json([
            'success' => true,
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function deliveryReportExport(Request $request, Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $data = $this->validate($request, [
            'delivery_start' => [
                'date_format:Y-m-d',
            ],
            'delivery_end' => [
                'date_format:Y-m-d',
            ],
            'docType' => [
                'required'
            ],
            'pickup' => [
                'required',
                'bool'
            ]
        ]);

        $orders = $business->orders()->with('products');

        if ($data['pickup']) $orders->where('customer_pickup', 1);
        else $orders->where('customer_pickup', 0);

        $fromDelDate = Date::parse($data['delivery_start']);
        $toDelDate = Date::parse($data['delivery_end']);

        if ($fromDelDate->gt($toDelDate)) {
            [$fromDelDate, $toDelDate] = [$toDelDate, $fromDelDate];
        }

        $orders->whereNotNull('slot_date');

        $orders->whereDate('slot_date', '>=', $fromDelDate->startOfDay()->toDateTimeString());
        $orders->whereDate('slot_date', '<=', $toDelDate->endOfDay()->toDateTimeString());

        if ($orders->count() < 1) {
            App::abort(422, 'You don\'t have any order between these date.');
        }

        SendExportedDeliveryReport::dispatch($business, [
            'delivery_start' => $data['delivery_start'],
            'delivery_end' => $data['delivery_end'],
            'docType' => $data['docType'],
            'pickup' => $data['pickup']
        ]);

        return Response::json([
            'success' => true,
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function markAsCompleted(Request $request, Business $business)
    {
        Gate::inspect('operate', $business)->authorize();

        foreach ($request->orders as $item) {
            if (isset($item['checked']) && $item['checked']) {
                $order = Order::find($item['id']);
                $order->status = OrderStatus::COMPLETED;
                $order->closed_at = Date::now();
                $message['status_changed'] = $order->status;

                $order->save();

                $order->notifyAboutStatusChanged('', $order->isCompleted());
            }
        }

        $orders = $business->orders()->with('products');

        $status = $request->get('status');

        if ($status === 'requires_business_action') {
            $orders->where('status', OrderStatus::REQUIRES_BUSINESS_ACTION);
        }

        $orders = $orders->orderByDesc('id')->get()->toArray();

        return Response::json([
            'orders' => $orders]);

    }

    /**
     * @param \App\Business $business
     * @param \App\Business\Order $order
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Business $business, Order $order)
    {
        Gate::inspect('view', $business)->authorize();

        if (in_array($order->status, [
            OrderStatus::REQUIRES_BUSINESS_ACTION,
            OrderStatus::REQUIRES_CUSTOMER_ACTION,
            OrderStatus::REQUIRES_PAYMENT_METHOD,
        ])) {
            $status = 'pending';
        } else {
            $status = $order->status;
        }

        // $order->status = OrderStatus::REQUIRES_CUSTOMER_ACTION;
        // $order->status = OrderStatus::REQUIRES_BUSINESS_ACTION;
        // $order->status = OrderStatus::REQUIRES_PAYMENT_METHOD;
        // $order->status = OrderStatus::COMPLETED;
        // $order->status = OrderStatus::CANCELED;
        // $order->status = OrderStatus::DRAFT;
        // $order->status = OrderStatus::EXPIRED;

        $order->load('charges', 'products');
        if ($order->isDraft()) {
            App::abort(404);
            // return Response::view('dashboard.business.order.edit', compact('business', 'order', 'status'));
        }

        return Response::view('dashboard.business.order.show', compact('business', 'order', 'status'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Order $order
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, Business $business, Order $order)
    {
        Gate::inspect('operate', $business)->authorize();

        if ($order->isCompleted()) {
            App::abort(403, 'You can\'t edit an order which is completed.');
        } elseif ($order->status !== OrderStatus::REQUIRES_BUSINESS_ACTION) {
            App::abort(403, 'You can\'t edit an order which doesn\'t require business action.');
        }

        $data = $this->validate($request, [
            'message' => 'nullable|string|max:200',
            'mark_as_ship' => 'nullable|bool',
        ]);

        $messages = $order->messages;

        $record = microtime(true);

        if ($data['mark_as_ship'] ?? false) {
            $order->status = OrderStatus::COMPLETED;
            $order->closed_at = Date::now();
            $message['status_changed'] = $order->status;
        }

        if (isset($data['message'])) {
            if ($data['mark_as_ship'] ?? false) {
                $message['shipping_details'] = $data['message'];
            } else {
                $message['plain_message'] = $data['message'];
            }
        }

        if (isset($message)) {
            $messages[$record] = $message;
        }

        $order->messages = $messages;
        $order->save();

        if (isset($message)) {
            $order->notifyAboutStatusChanged($data['message'], $order->isCompleted());

            Session::flash('success_message',
                'The order status/message has been updated and notified the buyer successfully.');
        }

        return Response::redirectToRoute('dashboard.business.order.show', [
            $business->getKey(),
            $order->getKey(),
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Order $order
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function cancel(Request $request, Business $business, Order $order)
    {
        Gate::inspect('operate', $business)->authorize();

        if (!($order->status == 'completed' || $order->status == 'requires_business_action')) {
            App::abort(403, 'You can\'t cancel an order which is not completed.');
        }
        $order->status = OrderStatus::CANCELED;
        $order->save();
        $order->notifyAboutStatusChanged('Your order has been cancelled', $order->isCompleted());

        if ($request->refund) {
            $charge = $business->charges->where('status', ChargeStatus::SUCCEEDED)->where('plugin_provider_order_id', $order->id)->first();
            if (!$charge) $charge = $order->charges->first();
            return Response::redirectToRoute('dashboard.business.charge.show', [
                $business->getKey(),
                $charge->getKey(),
            ]);
        } else {
            Session::flash('success_message',
                'The order has been canceled.');

            return Response::redirectToRoute('dashboard.business.order.show', [
                $business->getKey(),
                $order->getKey(),
            ]);
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Order $order
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateReference(Request $request, Business $business, Order $order)
    {
        Gate::inspect('operate', $business)->authorize();

        $data = $this->validate($request, [
            'reference' => 'nullable|string|max:255',
        ]);

        $order->reference = $data['reference'] ?? null;
        $order->save();

        return Response::json([
            'success' => true,
        ]);
    }

    public function delete(Request $request, Business $business, Order $order)
    {
        dd(__CLASS__ . '@' . __FUNCTION__, $order);
    }
}
