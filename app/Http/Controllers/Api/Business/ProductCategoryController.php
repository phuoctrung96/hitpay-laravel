<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Business\ProductCategory as ProductCategoryModel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\ProductCategory;
use App\Logics\Business\ProductCategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class ProductCategoryController extends Controller
{
    /**
     * ProductCategoryController constructor.
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
    public function index(BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        return ProductCategory::collection($business->productCategories()->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business\ProductCategory
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function store(Request $request, BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        $category = ProductCategoryRepository::store($request, $business);

        return new ProductCategory($category);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Business $business
     * @param \App\Business\ProductCategory $category
     *
     * @return \App\Http\Resources\Business\ProductCategory
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(BusinessModel $business, ProductCategoryModel $category)
    {
        Gate::inspect('view', $business)->authorize();

        return new ProductCategory($category);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\ProductCategory $category
     *
     * @return \App\Http\Resources\Business\ProductCategory
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function update(Request $request, BusinessModel $business, ProductCategoryModel $category)
    {
        Gate::inspect('update', $business)->authorize();

        $category = ProductCategoryRepository::update($request, $category);

        return new ProductCategory($category);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Business $business
     * @param \App\Business\ProductCategory $category
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function destroy(BusinessModel $business, ProductCategoryModel $category)
    {
        Gate::inspect('update', $business)->authorize();

        ProductCategoryRepository::delete($category);

        return Response::json([], 204);
    }
}
