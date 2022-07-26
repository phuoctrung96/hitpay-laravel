<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Actions\Business\Settings\UserManagement\Settings\Retrieve;
use App\Actions\Business\Settings\UserManagement\Settings\Store;
use App\Business;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class BusinessSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Business $business
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Business $business): \Illuminate\Http\JsonResponse
    {
        Gate::inspect('manage', $business)->authorize();

        $businessSettings = Retrieve::withBusiness($business)->process();

        return Response::json($businessSettings);
    }

    /**
     * @param Request $request
     * @param Business $business
     * @param Business\BusinessSettings $businessSettings
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function update(Request $request, Business $business, Business\BusinessSettings $businessSettings): \Illuminate\Http\JsonResponse
    {
        Gate::inspect('update', $business)->authorize();

        $businessSettings = Store::withData($request->post())
            ->business($business)
            ->setBusinessSetting($businessSettings)
            ->process();

        return Response::json($businessSettings);
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function store(Request $request, Business $business): \Illuminate\Http\JsonResponse
    {
        Gate::inspect('update', $business)->authorize();

        $businessSettings = Store::withData($request->post())->business($business)->process();

        return Response::json($businessSettings);
    }
}
