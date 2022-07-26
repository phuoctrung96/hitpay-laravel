<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class CouponController extends Controller
{
    /**
     * CouponController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Business $business): \Illuminate\Http\Response
    {
        return Response::view('dashboard.business.coupon.index', compact('business'));
    }

    /**
     * @param Business $business
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(Business $business): \Illuminate\Http\Response
    {
        Gate::inspect('manage', $business)->authorize();

        return Response::view('dashboard.business.coupon.create', compact('business'));
    }

    /**
     * @param Business $business
     * @param Business\Coupon $coupon
     * @return \Illuminate\Http\Response
     */
    public function edit(Business $business, Business\Coupon $coupon): \Illuminate\Http\Response
    {
        return Response::view('dashboard.business.coupon.edit', compact('business', 'coupon'));
    }
}
