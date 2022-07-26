<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Http\Controllers\Controller;
use App\Logics\BusinessRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;

class ShopUrlController extends Controller
{
    /**
     * ShopUrlController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a store url.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        $random = '43420024420024';

        return Response::json([
            'store_url' => str_replace($random, '', URL::route('shop.business', $random)),
            'identifier' => $business->identifier
        ]);
    }

    /**
     * Update business url.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function update(Request $request, BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        $business = BusinessRepository::updateIdentifier($request, $business);

        $random = '43420024420024';

        return Response::json([
            'store_url' => str_replace($random, '', URL::route('shop.business', $random)),
            'identifier' => $business->identifier,
        ]);
    }
}
