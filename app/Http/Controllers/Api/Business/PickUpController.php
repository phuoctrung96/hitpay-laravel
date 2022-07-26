<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Business\BusinessShopSettings;
use App\Business\Shipping as ShippingModel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Shipping;
use App\Logics\Business\ShippingRepository;
use App\Logics\BusinessRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;

class PickUpController extends Controller
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

        $shopSettings = $business->shopSettings()->first();

        $isEnabled = false;

        $slots = null;

        if ($shopSettings instanceof BusinessShopSettings) {
            $isEnabled = $shopSettings->can_pick_up;

            $slots = json_decode($shopSettings->slots);
        }

        return Response::json([
            'is_enabled' => $isEnabled,
            'address' => [
                'street' => $business->street,
                'city' => $business->city,
                'state' => $business->state,
                'country' => $business->country,
                'postal_code' => $business->postal_code
            ],
            'slots' => $slots
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
            'can_pick_up' => [
                'required',
                'bool',
            ],
            'slots' => [
                'nullable',
            ],
            'street' => [
                'nullable',
                'string',
                'max:255',
            ],
            'city' => [
                'nullable',
                'string',
                'max:255',
            ],
            'state' => [
                'nullable',
                'string',
                'max:255',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:16',
            ],
        ]);

        DB::transaction(function () use ($business, $data) {
            if ($business->shopSettings) {
                $business->shopSettings->update($data);
                $business->shopSettings->refresh();
            } else {
                $dataSetting['can_pick_up'] = $data['can_pick_up'];
                $dataSetting['slots'] = json_encode($data['slots']);
                $business->shopSettings()->create($dataSetting);
                $business->load('shopSettings');
            }

            $business->update($data);
        });

        return Response::json([
            'is_enabled' => $business->shopSettings->can_pick_up,
            'address' => [
                'street' => $business->street,
                'city' => $business->city,
                'state' => $business->state,
                'country' => $business->country,
                'postal_code' => $business->postal_code
            ],
            'slots' => json_decode($business->shopSettings->slots)
        ]);
    }
}
