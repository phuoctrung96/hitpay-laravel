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
use Illuminate\Validation\Rule;

class ProductController extends Controller
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

        $products = $this->requestHelperForBusinessWith($request, $business->products());

        if ($request->has('keywords')) {
            $keywords = $request->get('keywords');
            $keywords = is_array($keywords) ? $keywords : explode(' ', $keywords);
            $keywords = array_map(function ($value) {
                return trim($value);
            }, $keywords);
            $keywords = array_filter($keywords);
            $keywords = array_unique($keywords);

            if (count($keywords)) {
                foreach ($keywords as $keyword) {
                    $products->where('name', 'like', '%'.$keyword.'%');
                }
            } else {
                $products->whereNull('id');
            }
        }

        if ($request->has('category')) {
            $category = $request->get('category', null);

            if ($category) {
                $category = trim($category);
                $products->where('business_product_category_id', $category);
            }
        }

        $status = $request->get('status', 'published');
        $shopifyOnly = $request->get('shopify_only', 0);

        switch ($status) {

            case 'draft':
                $products->where('status', ProductStatus::DRAFT);

                break;

            default:
                $products->where('status', ProductStatus::PUBLISHED)
                    ->whereNotNull('published_at');

                if ($shopifyOnly) {
                    $products->whereNotNull('shopify_id');
                }
        }

        $products->orderByDesc('id');

        $perPage = $request->get('perPage');

        return Product::collection($products->paginate($perPage)->appends($request->except('page')));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business\Product
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function store(Request $request, BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        $request->merge([
            'publish' => true,
        ]);

        try {
            $product = ProductRepository::store($request, $business);

            $product->load(static::$relationships);

            return new Product($product);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ], $e->getCode() != 0 ? $e->getCode() : 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Business $business
     * @param \App\Business\Product $product
     *
     * @return \App\Http\Resources\Business\Product
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(BusinessModel $business, ProductModel $product)
    {
        Gate::inspect('view', $business)->authorize();

        $product->load(static::$relationships);

        return new Product($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Product $product
     *
     * @return \App\Http\Resources\Business\Product
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function update(Request $request, BusinessModel $business, ProductModel $product)
    {
        Gate::inspect('update', $business)->authorize();

        $product = ProductRepository::update($request, $product);

        $product->load(static::$relationships);

        return new Product($product);
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
    public function destroy(BusinessModel $business, ProductModel $product)
    {
        Gate::inspect('update', $business)->authorize();

        ProductRepository::delete($product);

        return Response::json([], 204);
    }

    /**
     * Send the specified resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Product $product
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function send(Request $request, BusinessModel $business, ProductModel $product)
    {
        Gate::inspect('operate', $business)->authorize();

        $data = $this->validate($request, [
            'customer_id' => [
                'required_without:email',
                Rule::exists('business_customers', 'id')->where('business_id', $business->getKey()),
            ],
            'email' => [
                'required_without:customer_id',
                'email',
            ],
        ]);

        // TODO - Send product to an email
        // TODO - Record who is receiving the email
        // TODO - Create a customer using $data['email'] if not yet created

        return Response::json([
            'success' => true,
        ]);
    }
}
