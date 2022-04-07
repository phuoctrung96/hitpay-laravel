<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\TaxDetail as TaxDetailResponse;
use App\Logics\Business\BasicDetailRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class BusinessTaxDetailController extends Controller
{
    /**
     * BusinessController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Update Tax Detail
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return string $imageUrl
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function update(Request $request, BusinessModel $business)
    {
        Gate::inspect('update', Auth::user(), $business)->authorize();

        $businessBasicDetail = new BasicDetailRepository($business);

        $business = $businessBasicDetail->updateTaxDetailsFromRequest($request);

        return new TaxDetailResponse($business);
    }

    public function show(Request $request, BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        return new TaxDetailResponse($business);
    }
}
