<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Business\Shipping as ShippingModel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Shipping;
use App\Logics\Business\ShippingRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class ShippingController extends Controller
{
    /**
     * ShippingController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        return Shipping::collection($business->productCategories()->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business\Shipping
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function store(Request $request, BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        $category = ShippingRepository::store($request, $business);

        return new Shipping($category);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Business $business
     * @param \App\Business\Shipping $shipping
     *
     * @return \App\Http\Resources\Business\Shipping
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(BusinessModel $business, ShippingModel $shipping)
    {
        Gate::inspect('view', $business)->authorize();

        return new Shipping($shipping);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Shipping $shipping
     *
     * @return \App\Http\Resources\Business\Shipping
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function update(Request $request, BusinessModel $business, ShippingModel $shipping)
    {
        Gate::inspect('update', $business)->authorize();

        $shipping = ShippingRepository::update($request, $shipping);

        return new Shipping($shipping);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Business $business
     * @param \App\Business\Shipping $shipping
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function destroy(BusinessModel $business, ShippingModel $shipping)
    {
        Gate::inspect('update', $business)->authorize();

        ShippingRepository::delete($shipping);

        return Response::json([], 204);
    }
}
