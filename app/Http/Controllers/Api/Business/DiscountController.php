<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Business\Discount as DiscountModel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Discount;
use App\Logics\Business\DiscountRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class DiscountController extends Controller
{
    /**
     * DiscountController constructor.
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
    public function index(Request $request, BusinessModel $business): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        Gate::inspect('view', $business)->authorize();

        $discounts = $business->discounts();

        if ($request->has('keywords')) {
            $keywords = $request->get('keywords');

            if (strlen($keywords) > 0) {
                $discounts->where('name', 'like', '%' . $keywords . '%')
                    ->orWhere('description', 'like', '%' . $keywords . '%');
            }
        }

        $perPage = $request->get('perPage', 10);

        $discounts->orderBy('updated_at', 'desc');

        return Discount::collection($discounts->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business\Discount
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function store(Request $request, BusinessModel $business): Discount
    {
        Gate::inspect('update', $business)->authorize();

        $discount = DiscountRepository::store($request, $business);

        return new Discount($discount);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Business $business
     * @param \App\Business\Discount $discount
     *
     * @return \App\Http\Resources\Business\Discount
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(BusinessModel $business, DiscountModel $discount): Discount
    {
        Gate::inspect('view', $business)->authorize();

        return new Discount($discount);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Discount $discount
     *
     * @return \App\Http\Resources\Business\Discount
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function update(Request $request, BusinessModel $business, DiscountModel $discount): Discount
    {
        Gate::inspect('update', $business)->authorize();

        $discount = DiscountRepository::update($request, $discount);

        return new Discount($discount);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Business $business
     * @param \App\Business\Discount $discount
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function destroy(BusinessModel $business, DiscountModel $discount): \Illuminate\Http\JsonResponse
    {
        Gate::inspect('update', $business)->authorize();

        DiscountRepository::delete($discount);

        return Response::json([], 204);
    }
}
