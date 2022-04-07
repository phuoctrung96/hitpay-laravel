<?php

namespace App\Http\Controllers\Shop;

use App\Business;
use App\Business\Order;
use App\Enumerations\Business\OrderStatus;
use App\Http\Controllers\Controller;
use App\Business\PaymentRequest;
use Illuminate\Support\Facades\Log;
use App\Manager\ChargeManagerInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;


class OrderController extends Controller
{

    public function status(Request $request, Business $business, Order $order)
    {
        if ($order->status != OrderStatus::REQUIRES_PAYMENT_METHOD && $order->status != OrderStatus::REQUIRES_BUSINESS_ACTION)
            App::abort(404);


        if ($order->status != OrderStatus::REQUIRES_BUSINESS_ACTION) {
            if (!$business->enabled_shipping){
                $orderStatus = OrderStatus::COMPLETED;
            }
            else {
                switch ($request->status) {
                    case 'canceled':
                        $orderStatus = OrderStatus::CANCELED;
                        break;
                    default:
                        $orderStatus = OrderStatus::REQUIRES_CUSTOMER_ACTION;
                }
            }

            $order->status = $orderStatus;

            DB::transaction(function () use ($order) {
                $order->save();
            });
        }

        $order->load('products');

        return Response::view('shop.order-status', [
            'business' => $business,
            'order' => $order,
            'status' => $request->status
        ]);
    }

    public function confirm(Request $request, Business $business, Order $order)
    {
        if ($order->status != OrderStatus::REQUIRES_BUSINESS_ACTION) {
            $paymentRequest = PaymentRequest::findOrFail($request->input('payment_request_id'));
            $this->validateRequest($request, $paymentRequest);

            $order->status = OrderStatus::REQUIRES_BUSINESS_ACTION;
            $business->orders()->save($order);

            DB::transaction(function () use ($order) {
                $order->save();
                $order->updateProductsQuantities();
                $order->notifyAboutNewOrder();
            });
        }
    }

    /**
     * Check the request
     *
     * @param Request $request
     * @param PaymentRequest $charge
     */
    private function validateRequest(Request $request, PaymentRequest $charge): void
    {
        if (!$request->has('hmac')) {
            App::abort(404);
        }

        if ($request->input('status') != 'completed') {
            App::abort(404);
        }

        $isValidHmac = false;
        foreach ($charge->business->apiKeys()->where('is_enabled', 1)->get() as $apiKey) {
            if (hash_equals($request->input('hmac'), $this->makeHmacFromRequest($request, (string)$apiKey->salt))) {
                $isValidHmac = true;
            }
        }

        if (!$isValidHmac) {
            App::abort(404);
        }
    }

    private function makeHmacFromRequest(Request $request, string $salt): string
    {
        return resolve(ChargeManagerInterface::class)
            ->generateSignatureArray($salt, $request->only(
                'payment_id',
                'payment_request_id',
                'phone',
                'amount',
                'currency',
                'status',
                'reference_number'
            ));
    }

}
