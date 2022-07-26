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
        return Response::view('dashboard.business.discount.index', compact('business'));
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

    public function edit(Business $business, Business\Discount $discount)
    {
        if (!isset($discount->id))
        {
            App::abort(404);
        }
        return Response::view('dashboard.business.discount.edit', compact('business', 'discount'));
    }
}
