<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Business\Image as ImageModel;
use App\Business\Product as ProductModel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Product;
use App\Logics\Business\ImageRepository;
use App\Logics\Business\ProductRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProductImageController extends Controller
{
    /**
     * ProductImageController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Product $product
     *
     * @return \App\Http\Resources\Business\Product|\Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function store(Request $request, BusinessModel $business, ProductModel $product)
    {
        Gate::inspect('update', $business)->authorize();

        try {
            ProductRepository::addImage($request, $business, $product);

            $product->load(ProductController::$relationships);

            return new Product($product);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() != 0 ? $e->getCode() : 500;

            $data = [
                'status' => 'failed',
                'message' => $e->getMessage()
            ];

            return response()->json($data, $statusCode);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Business $business
     * @param \App\Business\Product $product
     * @param \App\Business\Image $image
     *
     * @return \App\Http\Resources\Business\Product
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public static function destroy(BusinessModel $business, ProductModel $product, ImageModel $image)
    {
        Gate::inspect('update', $business)->authorize();

        try {
            ImageRepository::delete($image);

            $product->load(ProductController::$relationships);

            return new Product($product);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() != 0 ? $e->getCode() : 500;

            $data = [
                'status' => 'failed',
                'message' => $e->getMessage()
            ];

            return response()->json($data, $statusCode);
        }
    }
}
