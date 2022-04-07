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
class DiscountController extends Controller
{
    /**
     * Discount constructor.
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
        // todo
        $paginator = $business->discounts()->paginate(25);
        return Response::view('dashboard.business.discount.index', compact('business', 'paginator'));
    }

    /**
     * @param Business $business
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create( Business $business)
    {
        Gate::inspect('manage', $business)->authorize();

        return Response::view('dashboard.business.discount.create', compact('business'));
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
        $discountID = $request->get('id');
        $requestData = $this->validate($request, [
            'name' => 'required|string|max:255',
            'fixed_amount' => 'required|decimal:0,2',
            'percentage' => 'required|decimal:4,5',
            'minimum_cart_amount' => 'required|decimal:0,2',
            'is_promo_banner' => 'required|bool',
            'banner_text' => 'nullable|string|max:1000'
            ]);
        try {
            $requestData['minimum_cart_amount'] = getRealAmountForCurrency($business->currency, $requestData['minimum_cart_amount']);
            $requestData['fixed_amount'] = getRealAmountForCurrency($business->currency, $requestData['fixed_amount']);

            DB::beginTransaction();

            if ($requestData['is_promo_banner']) {
                $business->coupons()->update(['is_promo_banner' => false]);
                $business->discounts()->update(['is_promo_banner' => false]);
            }

            if(isset($discountID))
            {
                $business->discounts()->where('id', $discountID)->update($requestData);
            }
            else {
                $business->discounts()->create($requestData);
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
        Session::flash('success_message', !isset($discountID)? 'The discount has been created.'
        :'Successfully updated');
        return Response::json([
            'redirect_url' => URL::route('dashboard.business.discount.home', [
                $business->getKey(),
            ]),
        ]);
    }
    public function edit(Business $business, Business\Discount $discount)
    {
        if (!isset($discount->id))
        {
            App::abort(404);
        }
        return Response::view('dashboard.business.discount.edit', compact('business', 'discount'));
    }
    public function delete(Business $business, Business\Discount $discount)
    {
        if (!isset($discount->id))
        {
            App::abort(404);
        }
        try {
            $discount->delete();
        }
        catch (\Exception $exception)
        {
            throw $exception;
        }
        Session::flash('success_message', 'Successfully deleted');
        return redirect()->back();
    }
}
