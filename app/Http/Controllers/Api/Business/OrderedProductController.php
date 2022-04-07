<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Business\Order as OrderModel;
use App\Business\OrderedProduct as ProductModel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Order;
use App\Logics\Business\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderedProductController extends Controller
{
    /**
     * OrderedProductController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Store a newly created resource in storage.
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
    public static function store(Request $request, BusinessModel $business, OrderModel $order)
    {
        Gate::inspect('operate', $business)->authorize();

        $request->merge([
            'skip_quantity_check' => true,
        ]);

        OrderRepository::addProduct($request, $business, $order);

        $order->load(OrderController::$relationships);

        return new Order($order);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Order $order
     * @param \App\Business\OrderedProduct $product
     *
     * @return \App\Http\Resources\Business\Order
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function update(Request $request, BusinessModel $business, OrderModel $order, ProductModel $product)
    {
        Gate::inspect('operate', $business)->authorize();

        $request->merge([
            'skip_quantity_check' => true,
        ]);

        OrderRepository::updateProduct($request, $business, $order, $product);

        $order->load(OrderController::$relationships);

        return new Order($order);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Order $order
     * @param \App\Business\OrderedProduct $product
     *
     * @return \App\Http\Resources\Business\Order
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public static function delete(Request $request, BusinessModel $business, OrderModel $order, ProductModel $product)
    {
        Gate::inspect('operate', $business)->authorize();

        OrderRepository::removeProduct($business, $order, $product);

        $order->load(OrderController::$relationships);

        return new Order($order);
    }
}
