<?php

namespace App\Http\Controllers\MigratedApi;

use App\Business;
use App\Business\Product;
use App\Logics\Business\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * ProductController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * List the products.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function index(Request $request)
    {
        $business = $this->getBusiness($request);
        $paginator = $business->products()->with('images', 'variations');
        $status = $request->get('status');
        $status = strtolower($status);

        switch ($status) {

            case 'archived':
            case 'inactive':
                $paginator->whereNull('published_at');

                break;

            default:
                $paginator->whereNotNull('published_at');
        }

        $paginator->orderBy('updated_at', $request->get('order_direction') !== 'asc' ? 'desc' : 'asc');

        return $this->getProductsListObject($paginator->paginate(), $business);
    }

    /**
     * Filter products by keywords.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function search(Request $request)
    {
        $business = $this->getBusiness($request);
        $paginator = $business->products()->with('images', 'variations');

        $keywords = $request->get('keywords');
        $keywords = is_array($keywords) ? $keywords : explode(' ', $keywords);
        $keywords = array_map(function ($value) {
            return trim($value);
        }, $keywords);
        $keywords = array_filter($keywords);
        $keywords = array_unique($keywords);

        if (count($keywords)) {
            foreach ($keywords as $keyword) {
                $paginator->where('name', 'like', '%'.$keyword.'%');
            }
        } else {
            $paginator->whereNull('id');
        }

        return $this->getProductsListObject($paginator->paginate(), $business);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $id
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function show(Request $request, string $id)
    {
        $business = $this->getBusiness($request);

        $product = $business->products()->with('images', 'variations')->findOrFail($id);

        return Response::json($this->getProductObject($product, $business));
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        $business = $this->getBusiness($request);

        Gate::inspect('update', $business)->authorize();

        $replacement = [
            'name' => $request->get('name'),
            'description' => $request->get('remark'),
            'price' => $request->get('amount'),
            'quantity' => $request->get('quantity'),
            'quantity_alert_level' => $request->get('quantity_alert'),
            'publish' => true,
        ];

        if ($request->has('image')) {
            $replacement['image'] = $request->get('image');
        }

        $request->replace($replacement);

        try {
            $product = ProductRepository::store($request, $business);
        } catch (ValidationException $exception) {
            $mappedErrors = [];

            foreach ($exception->errors() as $key => $messages) {
                if ($key === 'description') {
                    $mappedErrors['remark'] = $messages;
                } elseif ($key === 'price') {
                    $mappedErrors['amount'] = $messages;
                } elseif ($key === 'quantity_alert_level') {
                    $mappedErrors['quantity_alert'] = $messages;
                } else {
                    $mappedErrors[$key] = $messages;
                }
            }

            return Response::json([
                'message' => $exception->getMessage(),
                'errors' => $mappedErrors,
            ], $exception->status);
        }

        $product->load([
            'variations',
            'images',
        ]);

        return Response::json($this->getProductObject($product, $business));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $id
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function update(Request $request, string $id)
    {
        $business = $this->getBusiness($request);

        $product = $business->productBases()->findOrFail($id);

        if ($product->isProduct()) {
            $product = $business->products()->findOrFail($product->getKey());
        } else {
            $product = $business->products()->findOrFail($product->parent_id);
        }

        if ($product->shopify_id) {
            App::abort(400);
        }

        Gate::inspect('update', $business)->authorize();

        $request->replace([
            'name' => $request->get('name'),
            'description' => $request->get('remark'),
            'price' => $request->get('amount'),
            'quantity' => $request->get('quantity'),
            'quantity_alert_level' => $request->get('quantity_alert'),
            'publish' => true,
        ]);

        try {
            $product = ProductRepository::update($request, $product);
        } catch (ValidationException $exception) {
            $mappedErrors = [];

            foreach ($exception->errors() as $key => $messages) {
                if ($key === 'description') {
                    $mappedErrors['remark'] = $messages;
                } elseif ($key === 'price') {
                    $mappedErrors['amount'] = $messages;
                } elseif ($key === 'quantity_alert_level') {
                    $mappedErrors['quantity_alert'] = $messages;
                } else {
                    $mappedErrors[$key] = $messages;
                }
            }

            return Response::json([
                'message' => $exception->getMessage(),
                'errors' => $mappedErrors,
            ], $exception->status);
        }

        $product = $product->refresh();

        $product->load([
            'variations',
            'images',
        ]);

        return Response::json($this->getProductObject($product, $business));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $id
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Throwable
     */
    public function archive(Request $request, string $id)
    {
        $business = $this->getBusiness($request);

        $product = $business->products()->findOrFail($id);

        ProductRepository::delete($product);

        return Response::json($this->getProductObject($product, $business));
    }

    private function getProductObject(Product $product, Business $business)
    {
        $data['id'] = $product->getKey();
        $data['type'] = 'unknown';
        $data['name'] = $product->name;
        $data['has_image'] = false;
        $data['is_shippable'] = $business->shippings_count > 0;
        $data['is_delivery_available'] = $data['is_shippable'];
        $data['remark'] = $product->description;
        $data['currency_code'] = strtoupper($product->currency);
        $data['amount'] = getReadableAmountByCurrency($product->currency, $product->price);
        $data['has_variations'] = $product->variations_count > 1;
        $data['is_pickup_allowed'] = $business->can_pick_up;
        $data['created_at'] = $product->created_at->getTimestamp();
        $data['updated_at'] = $product->updated_at->getTimestamp();
        $data['is_archived'] = !$product->isPublished();
        $data['is_active'] = $product->isPublished();
        $data['express_url'] = $product->shortcut_id
            ? URL::route('shortcut', $product->shortcut_id)
            : URL::route('shop.product', [
                $product->business_id,
                $product->getKey(),
            ]);

        if ($product->shopify_id) {
            $data['shopify'] = [
                'id' => $product->shopify_id,
                'inventory_item_id' => $product->shopify_inventory_item_id,
                'sku' => $product->shopify_stock_keeping_unit,
                'image_url' => $product->display('image'),
            ];
        }

        if ($product->shopify_image_url) {
            $data['has_image'] = true;
            $data['images'][] = [
                'id' => Str::random(),
                'url' => $product->display('image'),
                'remark' => $product->name,
            ];
        } elseif ($product->relationLoaded('images')) {
            foreach ($product->images as $image) {
                $data['has_image'] = true;
                $data['images'][] = [
                    'id' => $image->id,
                    'url' => $image->getUrl(),
                    'remark' => $product->name,
                ];
            }
        }

        if ($data['has_variations']) {
            $data['variation_types'] = array_filter([
                $product->variation_key_1,
                $product->variation_key_2,
                $product->variation_key_3,
            ]);

            foreach ($product->variations as $variation) {
                $thisVariation = [
                    'id' => $variation->getKey(),
                    'amount' => getReadableAmountByCurrency($product->currency, $variation->price),
                    'is_active' => $variation->isPublished(),
                ];

                if ($product->variation_key_1) {
                    $variationControls[] = [
                        'key' => $product->variation_key_1,
                        'value' => $variation->variation_value_1,
                    ];
                }

                if ($product->variation_key_2) {
                    $variationControls[] = [
                        'key' => $product->variation_key_2,
                        'value' => $variation->variation_value_2,
                    ];
                }

                if ($product->variation_key_3) {
                    $variationControls[] = [
                        'key' => $product->variation_key_3,
                        'value' => $variation->variation_value_3,
                    ];
                }

                if ($product->quantity === 1) {
                    $thisVariation['quantity'] = $variation->quantity;
                    $thisVariation['quantity_alert'] = $variation->quantity_alert_level;
                }

                $data['variations'][] = $thisVariation;
            }
        } else {
            $variation = $product->variations[0];

            $data['id'] = $variation->getKey();
            $data['amount'] = getReadableAmountByCurrency($product->currency, $variation->price);

            if ($product->quantity === 1) {
                $data['quantity'] = $variation->quantity;
                $data['quantity_alert'] = $variation->quantity_alert_level;
            }
        }

        return $data;
    }

    private function getProductsListObject(LengthAwarePaginator $paginator, Business $business)
    {
        $currentPage = $paginator->currentPage();

        $pagination = [];
        $pagination['self'] = $paginator->url($currentPage);
        $pagination['first'] = $paginator->url(1);

        if ($currentPage > 1) {
            $pagination['prev'] = $paginator->url($currentPage - 1);
        }

        $lastPage = $paginator->lastPage();

        if ($currentPage < $lastPage) {
            $pagination['next'] = $paginator->url($currentPage + 1);
        }

        $pagination['last'] = $paginator->url($lastPage);

        $data['meta'] = [
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $currentPage,
                'total_pages' => $lastPage,
                'links' => $pagination,
            ],
        ];

        $data['data'] = [];

        foreach ($paginator->items() as $item) {
            $data['data'][] = $this->getProductObject($item, $business);
        }

        return $data;
    }
}
