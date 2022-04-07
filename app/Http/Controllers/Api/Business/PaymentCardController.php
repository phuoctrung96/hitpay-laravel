<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Business\PaymentCard as PaymentCardModel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\PaymentCard;
use App\Logics\Business\PaymentCardRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class PaymentCardController extends Controller
{
    /**
     * CustomerController constructor.
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

        return PaymentCard::collection($business->productCategories()->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business\PaymentCard
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function store(Request $request, BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        $paymentCard = PaymentCardRepository::store($request, $business);

        return new PaymentCard($paymentCard);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Business $business
     * @param \App\Business\PaymentCard $paymentCard
     *
     * @return \App\Http\Resources\Business\PaymentCard
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(BusinessModel $business, PaymentCardModel $paymentCard)
    {
        Gate::inspect('view', $business)->authorize();

        return new PaymentCard($paymentCard);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\PaymentCard $paymentCard
     *
     * @return \App\Http\Resources\Business\PaymentCard
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function update(Request $request, BusinessModel $business, PaymentCardModel $paymentCard)
    {
        Gate::inspect('update', $business)->authorize();

        $paymentCard = PaymentCardRepository::update($request, $paymentCard);

        return new PaymentCard($paymentCard);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Business $business
     * @param \App\Business\PaymentCard $paymentCard
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function destroy(BusinessModel $business, PaymentCardModel $paymentCard)
    {
        Gate::inspect('update', $business)->authorize();

        PaymentCardRepository::delete($paymentCard);

        return Response::json([], 204);
    }
}
