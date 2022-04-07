<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Enumerations\Business\ImageGroup;
use App\Http\Controllers\Controller;
use App\Logics\Business\BusinessLogoRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class BusinessLogoController extends Controller
{
    /**
     * BusinessController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return string $imageUrl
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function store(Request $request, BusinessModel $business)
    {
        Gate::inspect('update', Auth::user(), $business)->authorize();

        $logoUrl = BusinessLogoRepository::store($request, $business, ImageGroup::LOGO);

        return Response::json(['logo_url' => $logoUrl], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Business $business
     * @param \App\Business\Product $product
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function destroy(BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        BusinessLogoRepository::delete($business);

        return Response::json([], 204);
    }
}
