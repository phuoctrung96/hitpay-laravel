<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Business\Shipping as ShippingModel;
use App\Enumerations\Business\OnboardingSteps;
use App\Enumerations\Business\RecurringCycle;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Shipping;
use App\Logics\Business\ShippingRepository;
use App\Logics\BusinessRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class StoreOnboardingStatusController extends Controller
{
    /**
     * ShippingController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        if (!$business->shopSettings)
            $business->shopSettings()->create();

        $business->load('shopSettings');

        return Response::json([
            'get_started' => $business->shopSettings->get_started,
            'has_order' => $business->orders->count() > 0 ? 1 : 0
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function update(Request $request, BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        $data = $this->validate($request, [
            'key' => [
                'required',
                Rule::in(OnboardingSteps::listConstants()),
            ],
            'value' => [
                'required',
                'boolean'
            ]
        ]);

        if (!$business->shopSettings)
            $business->shopSettings()->create();

        DB::transaction(function () use ($business, $data) {

            $get_started = $business->shopSettings->get_started;
            $get_started[$data['key']] = $data['value'];

            $business->shopSettings->get_started = $get_started;
            $business->shopSettings->save();
        });

        return Response::json([
            'get_started' => $business->shopSettings->get_started,
            'has_order' => $business->orders->count() > 0 ? 1 : 0
        ]);
    }
}
