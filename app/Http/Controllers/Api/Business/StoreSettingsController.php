<?php

namespace App\Http\Controllers\Api\Business;

use App\Actions\Business\StoreSettings\Retrieve;
use App\Actions\Business\StoreSettings\Update;
use App\Business as BusinessModel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\ShopSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class StoreSettingsController extends Controller
{
    /**
     * ThemeCustomisationController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @param BusinessModel $business
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(BusinessModel $business): \Illuminate\Http\JsonResponse
    {
        Gate::inspect('view', $business)->authorize();

        $storeSettings = Retrieve::withBusiness($business)->process();

        if ($storeSettings === null) {
            return Response::json([
                'business' => $business,
                'store_settings' => null
            ]);
        }

        return Response::json([
            'business' => $business,
            'store_settings' => new ShopSettings($storeSettings),
        ]);
    }

    /**
     * @param Request $request
     * @param BusinessModel $business
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function update(Request $request, BusinessModel $business): \Illuminate\Http\JsonResponse
    {
        Gate::inspect('update', $business)->authorize();

        $storeSettings = Update::withBusiness($business)->data($request->post())->process();

        return Response::json([
            'business' => $business,
            'store_settings' => new ShopSettings($storeSettings),
        ]);
    }
}
