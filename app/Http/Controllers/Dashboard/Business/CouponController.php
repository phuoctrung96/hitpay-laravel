<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
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
    public function index(Request $request, Business $business)
    {
        $paginator = $business->coupons()->paginate(25);
        return Response::view('dashboard.business.coupon.index', compact('business', 'paginator'));
    }

    /**
     * @param Business $business
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create( Business $business)
    {
        Gate::inspect('manage', $business)->authorize();

        return Response::view('dashboard.business.coupon.create', compact('business'));
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\JsonResponse
     * @throws ValidationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function store(Request $request, Business $business)
    {
        Gate::inspect('manage', $business)->authorize();
        $couponID = $request->get('id');
        $requestData = $this->validate($request, [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'fixed_amount' => 'required|decimal:0,2',
            'percentage' => 'required|decimal:4,5',
            'is_promo_banner' => 'required|bool',
            'banner_text' => 'nullable|string|max:1000'
            ]);
        try {
            $requestData['fixed_amount'] = getRealAmountForCurrency($business->currency, $requestData['fixed_amount']);

            DB::beginTransaction();

            // Disable current banner
            if ($requestData['is_promo_banner']) {
                $business->discounts()->update(['is_promo_banner' => false]);
                $business->coupons()->update(['is_promo_banner' => false]);
            }

            if(isset($couponID))
            {
                $business->coupons()->where('id', $couponID)->update($requestData);
            } else {
                $coupon = $business->coupons()->where('name', $requestData['name'])->orWhere('code', $requestData['code'])->get();
                if ($coupon && $coupon->count() > 0) {
                    $nameExists = in_array($requestData['name'], $coupon->pluck('name')->toArray()) ?? false;
                    $codeExists = in_array($requestData['code'], $coupon->pluck('code')->toArray()) ?? false;
                    return Response::json(['coupon_exists' => true, 'name' => $nameExists, 'code' => $codeExists]);
                }
                $business->coupons()->create($requestData);
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
        Session::flash('success_message', !isset($couponID)? 'The coupon has been created.'
        :'Successfully updated');
        return Response::json([
            'redirect_url' => URL::route('dashboard.business.coupon.home', [
                $business->getKey(),
            ]),
        ]);
    }

    public function edit(Business $business, Business\Coupon $coupon)
    {
        if (!isset($coupon->id))
        {
            App::abort(404);
        }
        return Response::view('dashboard.business.coupon.edit', compact('business', 'coupon'));
    }
    public function delete(Business $business, Business\Coupon $coupon)
    {
        if (!isset($coupon->id))
        {
            App::abort(404);
        }
        try {
            $coupon->delete();
        }
        catch (\Exception $exception)
        {
            throw $exception;
        }
        Session::flash('success_message', 'Successfully deleted');
        return redirect()->back();
    }
}
