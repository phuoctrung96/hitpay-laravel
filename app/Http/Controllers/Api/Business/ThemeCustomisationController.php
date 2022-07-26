<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Business\Shipping as ShippingModel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Shipping;
use App\Logics\Business\ShippingRepository;
use App\Logics\BusinessRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class ThemeCustomisationController extends Controller
{
    /**
     * ThemeCustomisationController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Get a theme customisation.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        $themeCustomisation = $business->checkoutCustomisation();

        return Response::json([
            'theme' => $themeCustomisation->theme,
            'tint_color' => $themeCustomisation->tint_color,
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

        $validatedData = $request->validate([
            'customColor' => ['required', 'string', 'max:7'],
            'theme' => [ 'required', Rule::in(['hitpay', 'custom', 'light']) ],
        ]);

        $themeCustomisation = $business->updateCustomisation($validatedData);

        return Response::json([
            'theme' => $themeCustomisation->theme,
            'tint_color' => $themeCustomisation->tint_color,
        ]);
    }
}
