<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Business\Product as ProductModel;
use App\Enumerations\Business\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Product;
use App\Logics\Business\ProductRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductsController extends Controller
{
    /**
     * Relationships can be loaded.
     *
     * @var array
     */
    public static $relationships = [
        'category',
        'variations',
        'images',
        'tax',
    ];

    /**
     * ProductController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request, BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        $products = ProductRepository::getList($request, $business);

        $perPage = $request->get('perPage');

        return Product::collection($products->paginate($perPage)->appends($request->except('page')));
    }

    /**
     * Change status of given products.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function changeStatus(Request $request, BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        $data = Validator::validate($request->all(), [
            'products' => [
                'required',
                'array',
            ],
            'status' => [
                'required',
                'in:'.implode(",",ProductStatus::listConstants())
            ],
        ]);

        foreach ($data['products'] as $productId){
            $business->products()->find($productId)->update(['status' => $data['status']]);
        }

        return Response::json([], 200);
    }
}
