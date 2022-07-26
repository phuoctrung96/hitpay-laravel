<?php

namespace App\Http\Controllers\Api\Business;

use App\Actions\Business\Coupons\Destroy;
use App\Actions\Business\Coupons\Store;
use App\Actions\Business\Coupons\Update;
use App\Business as BusinessModel;
use App\Business\Discount as DiscountModel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Coupon;
use App\Http\Resources\Business\Discount;
use App\Logics\Business\DiscountRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class CouponController extends Controller
{
    /**
     * DiscountController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @param Request $request
     * @param BusinessModel $business
     * @return \Illuminate\Http\Resources\Json\JsonResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request, BusinessModel $business): \Illuminate\Http\Resources\Json\JsonResource
    {
        Gate::inspect('view', $business)->authorize();

        $coupons = $business->coupons();

        if ($request->has('keywords')) {
            $keywords = $request->get('keywords');

            if (strlen($keywords) > 0) {
                $coupons = $business->coupons()
                    ->where('name', 'like', '%' . $keywords . '%');
            }
        }

        $perPage = $request->get('perPage', 10);

        $coupons->orderBy('updated_at', 'desc');

        return Coupon::collection($coupons->paginate($perPage));
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Business $business
     * @param \App\Business\Coupon $coupon
     *
     * @return \App\Http\Resources\Business\Coupon
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(BusinessModel $business, \App\Business\Coupon $coupon): \App\Http\Resources\Business\Coupon
    {
        Gate::inspect('view', $business)->authorize();

        return new Coupon($coupon);
    }

    /**
     * @param Request $request
     * @param BusinessModel $business
     * @return Coupon
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request, BusinessModel $business): Coupon
    {
        Gate::inspect('manage', $business)->authorize();

        $coupon = Store::withBusiness($business)->data($request->post())->process();

        return new Coupon($coupon);
    }

    /**
     * @param Request $request
     * @param BusinessModel $business
     * @param \App\Business\Coupon $coupon
     * @return Coupon
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     */
    public function update(Request $request, BusinessModel $business, \App\Business\Coupon $coupon): Coupon
    {
        Gate::inspect('update', $business)->authorize();

        $discount = Update::withBusiness($business)
            ->data($request->post())
            ->setCoupon($coupon)
            ->process();

        return new Coupon($discount);
    }

    /**
     * @param BusinessModel $business
     * @param \App\Business\Coupon $coupon
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function destroy(BusinessModel $business, \App\Business\Coupon $coupon): \Illuminate\Http\JsonResponse
    {
        Gate::inspect('update', $business)->authorize();

        Destroy::withBusiness($business)->setCoupon($coupon)->process();

        return Response::json([], 204);
    }
}
