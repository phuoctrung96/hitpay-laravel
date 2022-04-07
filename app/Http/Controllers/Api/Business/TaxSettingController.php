<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\TaxSetting as TaxSettingResource;
use App\Business\TaxSetting as TaxSettingModel;
use App\Logics\Business\TaxSettingRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class TaxSettingController extends Controller
{
    /**
     * TaxSettingController constructor.
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
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request, BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        $taxSettings = TaxSettingRepository::getList($request, $business);

        return TaxSettingResource::collection($taxSettings);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business\TaxSetting
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function store(Request $request, BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        $taxSetting = TaxSettingRepository::store($request, $business);

        return new TaxSettingResource($taxSetting);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Business $business
     * @param \App\Business\TaxSetting $taxsetting
     *
     * @return \App\Http\Resources\Business\TaxSetting
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(BusinessModel $business, TaxSettingModel $taxsetting)
    {
        Gate::inspect('view', $business)->authorize();

        return new TaxSettingResource($taxsetting);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\TaxSetting $taxsetting
     *
     * @return \App\Http\Resources\Business\TaxSetting
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function update(Request $request, BusinessModel $business, TaxSettingModel $taxsetting)
    {
        Gate::inspect('update', $business)->authorize();

        $result = TaxSettingRepository::update($request, $taxsetting);

        return new TaxSettingResource($result);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Business $business
     * @param \App\Business\TaxSetting $taxsetting
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function destroy(BusinessModel $business, TaxSettingModel $taxsetting)
    {
        Gate::inspect('update', $business)->authorize();

        TaxSettingRepository::delete($taxsetting);

        return Response::json([], 204);
    }
}
