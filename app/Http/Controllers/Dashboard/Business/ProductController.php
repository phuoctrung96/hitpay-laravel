<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Business\HotglueProductTracker;
use App\Business\Image;
use App\Business\Product;
use App\Business\ProductVariation;
use App\Business\ProductCategory;
use App\Enumerations\Business\ImageGroup;
use App\Enumerations\CountryCode;
use App\Enumerations\CurrencyCode;
use App\Exports\ProductFeedTemplate;
use App\Http\Controllers\Controller;
use App\Jobs\SendExportedProducts;
use App\Logics\Business\ProductRepository;
use App\Shortcut;
use Carbon\Carbon;
use Exception;
use HitPay\Image\Processor as ImageProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{

    private $imageLimit;

    /**
     * ProductController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->imageLimit = 6;
    }

    /**
     * List all products.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request, Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $products = $business->products()->with('variations', 'images');

        $keywords = $request->get('keywords');

        if ($keywords) {
            $keywords = is_array($keywords) ? $keywords : explode(' ', $keywords);
            $keywords = array_map(function ($value) {
                return trim($value);
            }, $keywords);
            $keywords = array_filter($keywords);
            $keywords = array_unique($keywords);

            if (count($keywords)) {
                foreach ($keywords as $keyword) {
                    $products->where('name', 'like', '%' . $keyword . '%');
                }
            }
        }

        $status = $request->get('status', 'published');
        $shopifyOnly = $request->get('shopify_only', 0);

        switch ($status) {

            case 'draft':
                $products->whereNull('published_at');

                break;

            default:
                $products->whereNotNull('published_at');

                if ($shopifyOnly) {
                    $products->whereNotNull('shopify_id');
                }
        }

        $products = $products->orderByDesc('created_at')->get();

        $feed_url = null;
        if (isset($business->fb_feed_slot)) {
            $feed_url = 'http://' . config('app.subdomains.shop') . '/' . $business->fb_feed_slot . '/products';
        }
        $random = '43420024420024';
        $data = [
            'fb_feed_url' => $feed_url,
            'prefixes' => [
                'shop_url' => str_replace($random, '', URL::route('shop.business', $random)),
                'checkout_url' => str_replace($random, '', URL::route('checkout.store', $random)),
            ],
        ];
        $product_attrs = [];

        foreach ($products as $product) {
            $isShopify = false;
            if ($sku = $product->stock_keeping_unit) {
                if (HotglueProductTracker::whereStockKeepingUnit($sku)->whereIsShopify(true)->first()) {
                    $isShopify = true;
                }
            }
            $product_attrs['image'][] = $product->display('image');
            $product_attrs['price'][] = $product->display('price');
            $product_attrs['manageable'][] = $product->isManageable();
            $product_attrs['quantity'][] = $product->variations->sum('quantity');
            $product_attrs['is_shopify'][] = $isShopify;
        }

        $currentBusinessUser = resolve(\App\Services\BusinessUserPermissionsService::class)->getBusinessUser(Auth::user(), $business);

        return Response::view('dashboard.business.product.index', compact('business', 'products', 'status', 'data', 'currentBusinessUser') + [
                'shopify_only' => $shopifyOnly,
                'product_attrs' => $product_attrs
            ]);
    }

    /**
     * Show a product.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Product $product
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Request $request, Business $business, Product $product)
    {
        if (!$product->shopify_id && Gate::inspect('manage', $business)->allowed()) {
            return Response::redirectToRoute('dashboard.business.product.edit', [
                $business->getKey(),
                $product->getKey(),
                'page' => $request->get('index_page', 1),
                'shopify_only' => $request->get('index_shopify_only', 0),
                'status' => $request->get('index_status', 'published'),
            ]);
        }

        Gate::inspect('view', $business)->authorize();

        $product->load('variations', 'images');

        return Response::view('dashboard.business.product.show', compact('business', 'product'));
    }

    /**
     * Show product create form.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(Business $business)
    {
        Gate::inspect('manage', $business)->authorize();

        $categories = $business->productCategories()->where('active', 1)->get();

        return Response::view('dashboard.business.product.create', compact('business', 'categories'));
    }

    /**
     * Create product.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function store(Request $request, Business $business)
    {
        Gate::inspect('manage', $business)->authorize();

        $requestData = $this->validate($request, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:65536',
            'stock_keeping_unit' => 'nullable|string|max:32',
            'price' => 'required|decimal:0,2',
            'business_product_category_id' => 'nullable|string',
            'is_manageable' => 'required|bool',
            'quantity' => 'required_if:is_manageable,true|required_with:quantity_alert_level|int|min:0',
            'quantity_alert_level' => 'nullable|int|min:0',
            'image1' => 'nullable|image',
            'image2' => 'nullable|image',
            'image3' => 'nullable|image',
            'image4' => 'nullable|image',
            'image5' => 'nullable|image',
            'image6' => 'nullable|image',
            'variation' => 'nullable|array|max:100',
            'variation.*.values' => 'required_with:variation|array|max:3',
            'variation.*.values.*.key' => 'required_with:variation.*.values',
            'variation.*.values.*.value' => 'required_with:variation.*.values.*.key|string',
            'variation.*.quantity' => 'required_if:is_manageable,true|required_with:variation.*.quantity_alert_level|int|min:0',
            'variation.*.quantity_alert_level' => 'nullable|int|min:0',
            'variation.*.price' => 'required|decimal:0,2',
            'publish' => 'required|bool',
            'is_pinned' => 'nullable|bool'
        ]);

        $productData = [
            'name' => $requestData['name'],
            'description' => $requestData['description'] ?? null,
            'stock_keeping_unit' => $requestData['stock_keeping_unit'] ?? null,
            'currency' => $business->currency,
            'price' => getRealAmountForCurrency($business->currency, $requestData['price']),
            'quantity' => $requestData['is_manageable'] ?? false ? 1 : 0,
            'business_product_category_id' => $requestData['business_product_category_id'],
            'is_pinned' => $requestData['is_pinned'] ?? false
        ];

        if ($requestData['publish']) {
            $productData['published_at'] = Date::now();
        }

        if (isset($requestData['variation'])) {
            $i = 1;

            foreach (Arr::sort($requestData['variation'][0]['values'], function ($value) {
                return $value['key'];
            }) as $key) {
                $productData['variation_key_' . $i] = $key['key'];
                $variationKeyMap[$key['key']] = 'variation_value_' . $i;

                $i++;
            }

            if (isset($variationKeyMap)) {
                foreach ($requestData['variation'] as $variation) {
                    $variationName = collect(Arr::sort($variation['values'], function ($value) {
                        return $value['key'];
                    }))->pluck('value');

                    $temp = [
                        'business_id' => $business->id,
                        'description' => $variationName->implode(' / '),
                        'price' => getRealAmountForCurrency($business->currency, $variation['price']),
                    ];

                    if ($productData['quantity'] === 1) {
                        $temp['quantity'] = $variation['quantity'];
                        $temp['quantity_alert_level'] = $variation['quantity_alert_level'] ?? null;
                    }

                    foreach ($variation['values'] as $value) {
                        if (!isset($variationKeyMap[$value['key']])) {
                            continue 2;
                        }

                        $temp[$variationKeyMap[$value['key']]] = $value['value'];
                    }

                    $variationsData[] = $temp;
                }
            }
        } else {
            $temp = [
                'business_id' => $business->id,
                'price' => getRealAmountForCurrency($business->currency, $requestData['price']),
            ];

            if ($productData['quantity'] === 1) {
                $temp['quantity'] = $requestData['quantity'];
                $temp['quantity_alert_level'] = $requestData['quantity_alert_level'] ?? null;
            }

            $variationsData[] = $temp;
        }

        try {
            DB::beginTransaction();

            $product = $business->products()->create($productData);

            $product->variations()->createMany($variationsData);

            for ($i = 1; $i < $this->imageLimit; $i++) {
                if ($request->hasFile('image' . $i)) {
                    ImageProcessor::new($business, ImageGroup::PRODUCT, $requestData['image' . $i], $product)
                        ->setCaption($product->name)
                        ->process();
                }
            }

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();

            throw $exception;
        }

        if ($product->wasChanged('published_at')) {
            Session::flash('success_message', 'The product \'' . $product->name . '\' has been published.');
        } else {
            Session::flash('success_message', 'The product \'' . $product->name . '\' has been created.');
        }

        return Response::json([
            'redirect_url' => URL::route('dashboard.business.product.index', [
                $business->getKey(),
                'status' => $product->isPublished() ? 'published' : 'draft',
            ]),
        ]);
    }

    /**
     * Show product edit form.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Product $product
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(Request $request, Business $business, Product $product)
    {
        if ($product->shopify_id) {
            return Response::redirectToRoute('dashboard.business.product.show', [
                $business->getKey(),
                $product->getKey(),
            ]);
        }

        Gate::inspect('manage', $business)->authorize();

        $product->load('variations', 'images');

        $product = $this->getProductObject($product);

        $categories = $business->productCategories()->where('active', 1)->get();

        if (!isset($product['image'])) $product['image'] = [];

        $product['is_shopify'] = false;
        if ($sku = $product['stock_keeping_unit']) {
            if (HotglueProductTracker::whereStockKeepingUnit($sku)->whereIsShopify(true)->first()) {
                $product['is_shopify'] = true;
            }
        }

        return Response::view('dashboard.business.product.form', compact('business', 'product', 'categories'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Product $product
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, Business $business, Product $product)
    {
        Gate::inspect('manage', $business)->authorize();

        if ($product->shopify_id) {
            App::abort(400);
        }

        $requestData = $this->validate($request, [
            'name' => 'required|string|max:255',
            'price' => 'required|decimal:0,2',
            'description' => 'nullable|string|max:65536',
            'stock_keeping_unit' => 'nullable|string|max:32',
            'business_product_category_id' => 'nullable|string',
            'is_manageable' => 'nullable|bool',
            'quantity' => 'required_if:is_manageable,true|required_with:quantity_alert_level|int|min:0',
            'quantity_alert_level' => 'nullable|int|min:0',
            'variation' => 'nullable|array',
            'variation.*.id' => 'required_with:variation|string',
            'variation.*.quantity' => 'required_if:is_manageable,true|required_with:variation.*.quantity_alert_level|int|min:0',
            'variation.*.quantity_alert_level' => 'nullable|int|min:0',
            'variation.*.price' => 'required|decimal:0,2',
            'new_variation' => 'nullable|array|max:100',
            'new_variation.*.values' => 'required_with:new_variation|array|max:3',
            'new_variation.*.values.*.key' => 'required_with:new_variation.*.values',
            'new_variation.*.values.*.value' => 'required_with:new_variation.*.values.*.key|string',
            'new_variation.*.quantity' => 'required_if:is_manageable,true|required_with:new_variation.*.quantity_alert_level|int|min:0',
            'new_variation.*.quantity_alert_level' => 'nullable|int|min:0',
            'new_variation.*.price' => 'required|decimal:0,2',
            'publish' => 'required|bool',
            'is_pinned' => 'nullable|bool'
        ]);

        $product->name = $requestData['name'];
        $product->price = getRealAmountForCurrency($product->currency, $requestData['price']);
        $product->description = $requestData['description'] ?? null;
        $product->stock_keeping_unit = $requestData['stock_keeping_unit'] ?? null;
        $product->quantity = $requestData['is_manageable'] ?? false ? 1 : 0;
        $product->business_product_category_id = $requestData['business_product_category_id'] ?? null;
        $product->is_pinned = $requestData['is_pinned'] ?? false;

        if ($requestData['publish']) {
            $product->published_at = $product->published_at ?: Date::now();
        } else {
            $product->published_at = null;
        }

        if (isset($requestData['variation'])) {
            $product->save();

            $variations = $product->variations()->findMany(collect($requestData['variation'])->pluck('id'));

            foreach ($requestData['variation'] as $variation) {
                $model = $variations->find($variation['id']);

                if ($model instanceof ProductVariation) {
                    if ($product->quantity > 0) {
                        $model->quantity = $variation['quantity'] ?? 0;
                        $model->quantity_alert_level = $variation['quantity_alert_level'] ?? null;
                    } else {
                        $model->quantity = null;
                        $model->quantity_alert_level = null;
                    }

                    $model->price =
                        getRealAmountForCurrency($product->currency, $variation['price'] ?? $requestData['price']);
                    $model->save();
                }
            }
        } elseif (isset($requestData['new_variation'])) {
            $product->variations()->delete();
            $product->save();

            $i = 1;

            foreach (Arr::sort($requestData['new_variation'][0]['values'], function ($value) {
                return $value['key'];
            }) as $key) {
                $var_key = 'variation_key_' . $i;
                $product->$var_key = $key['key'];
                $variationKeyMap[$key['key']] = 'variation_value_' . $i;

                $i++;
            }
            $product->save();

            if (isset($variationKeyMap)) {
                foreach ($requestData['new_variation'] as $variation) {
                    $variationName = collect(Arr::sort($variation['values'], function ($value) {
                        return $value['key'];
                    }))->pluck('value');

                    $temp = [
                        'business_id' => $business->id,
                        'description' => $variationName->implode(' / '),
                        'price' => getRealAmountForCurrency($business->currency, $variation['price']),
                    ];

                    if ($product->quantity === 1) {
                        $temp['quantity'] = $variation['quantity'];
                        $temp['quantity_alert_level'] = $variation['quantity_alert_level'] ?? null;
                    }

                    foreach ($variation['values'] as $value) {
                        if (!isset($variationKeyMap[$value['key']])) {
                            continue 2;
                        }

                        $temp[$variationKeyMap[$value['key']]] = $value['value'];
                    }

                    $variationsData[] = $temp;
                }

                $product->variations()->createMany($variationsData);
            }
        } else {
            $variations = $product->variations()->get();

            if ($variations->count() > 1) {
                Log::info('More than 1 variation found for product without variations. ID: ' . $product->getKey());
            }

            $variation = $variations->first();

            $variation->price = getRealAmountForCurrency($business->currency, $requestData['price']);

            if ($product->quantity > 0) {
                $variation->quantity = $requestData['quantity'];
                $variation->quantity_alert_level = $requestData['quantity_alert_level'] ?? null;
            } else {
                $variation->quantity = null;
                $variation->quantity_alert_level = null;
            }
            $product->save();
            $variation->save();
        }

        if ($product->wasChanged('published_at')) {
            if (!$requestData['publish']) {
                Session::flash('success_message', 'The product \'' . $product->name . '\' has been unpublished.');
            } else {
                Session::flash('success_message', 'The product \'' . $product->name . '\' has been published.');
            }
        } else {
            Session::flash('success_message', 'The product \'' . $product->name . '\' has been updated.');
        }

        return JsonResponse::create([
            'redirect' => route('dashboard.business.product.index', [
                $business->getKey(),
                'status' => $product->isPublished() ? 'published' : 'draft',
            ]),
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Product $product
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function duplicate(Request $request, Business $business, Product $product)
    {
        Gate::inspect('manage', $business)->authorize();

        $duplicate = $product->replicate(array('variations_count', 'images_count'));

        if ($duplicate->business_product_category_id) {
                $arr = [];
                foreach ($duplicate->business_product_category_id as $category) {
                    $arr[] = $category->id;
                }

                $duplicate->business_product_category_id = json_encode($arr);
        }
        $productsSKUs = Product::whereBusinessId($business->id)->where('stock_keeping_unit', 'LIKE', $duplicate->stock_keeping_unit.'%')->get();
        $duplicate->stock_keeping_unit .= $productsSKUs->count();
        $duplicate->push();

        $product->load('variations', 'images');

        foreach ($product->getRelations() as $relation => $entries) {
            foreach ($entries as $entry) {
                $e = $entry->replicate();
                if ($e->push()) {
                    $duplicate->{$relation}()->save($e);
                }
            }
        }
        Session::flash('success_message', 'The product \'' . $product->name . '\' has been successfully duplicated.');

        return JsonResponse::create(['redirect' => route('dashboard.business.product.index', [$business->getKey(),]),]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Product $product
     * @param \App\Business\Image $image
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public
    function addImage(Request $request, Business $business, Product $product, Image $image)
    {
        Gate::inspect('manage', $business)->authorize();

        if ($product->shopify_id) {
            App::abort(400);
        }

        ProductRepository::addImage($request, $business, $product);

        $product->load('variations', 'images');

        return Response::json($this->getProductObject($product));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Product $product
     * @param \App\Business\Image $image
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public
    function deleteImage(Request $request, Business $business, Product $product, Image $image)
    {
        Gate::inspect('manage', $business)->authorize();

        if ($product->shopify_id) {
            App::abort(400);
        }

        DB::transaction(function () use ($image) {
            $image->delete();
        });

        $product->load('variations', 'images');

        $product = $this->getProductObject($product);

        if (!isset($product['image'])) $product['image'] = ['id' => '', 'url' => ''];

        return Response::json($product);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Product $product
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public
    function addVariation(Request $request, Business $business, Product $product)
    {
        Gate::inspect('manage', $business)->authorize();

        if ($product->shopify_id) {
            App::abort(404);
        }

        for ($i = 1; $i <= 3; $i++) {
            if (!is_null($product->{'variation_key_' . $i})) {
                $variationKeyMap[$product->{'variation_key_' . $i}] = 'variation_value_' . $i;
            }
        }

        if (!isset($variationKeyMap)) {
            App::abort(403);
        }

        $quantityRule = $product->isManageable() ? 'required' : 'nullable';

        $variation = $this->validate($request, [
            'values' => 'required|array|min:' . count($variationKeyMap),
            'values.*.key' => 'required_with:variation|in:' . implode(',', array_keys($variationKeyMap)) . '|distinct',
            'values.*.value' => 'required_with:variation.*.key|string',
            'quantity' => 'required_with:variation.*.quantity_alert_level|' . $quantityRule . '|int|min:0',
            'quantity_alert_level' => 'nullable|int|min:0',
            'price' => 'required|decimal:0,2',
        ]);

        $variationName = collect(Arr::sort($variation['values'], function ($value) {
            return $value['key'];
        }))->pluck('value');

        $temp = [
            'business_id' => $product->business_id,
            'description' => $variationName->implode(' / '),
            'price' => getRealAmountForCurrency($product->currency, $variation['price']),
        ];

        foreach ($variation['values'] as $value) {
            if (!isset($variationKeyMap[$value['key']])) {
                throw ValidationException::withMessages([
                    $value['key'] => $value['key'] . ' is invalid.',
                ]);
            }

            $temp[$variationKeyMap[$value['key']]] = $value['value'];
        }

        if ($product->isManageable()) {
            $temp['quantity'] = $variation['quantity'];
            $temp['quantity_alert_level'] = $variation['quantity_alert_level'] ?? null;
        }

        $product->variations()->create($temp);

        // return new \App\Http\Resources\Product($product->refresh()->load([
        //     'variation' => function ($builder) {
        //         $builder->orderBy('variation_1_value')->orderBy('variation_2_value')->orderBy('variation_3_value');
        //     },
        // ]));

        $product->load('variations', 'images');

        return Response::json($this->getProductObject($product));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Product $product
     * @param \App\Business\ProductVariation $variation
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public
    function deleteVariation(Request $request, Business $business, Product $product, ProductVariation $variation)
    {
        Gate::inspect('manage', $business)->authorize();

        if ($product->shopify_id) {
            App::abort(404);
        }

        if ($product->variations()->count() <= 2) {
            return Response::json([
                'message' => 'You must keep at least 2 variations for a product with variations.',
            ], 403);
        }

        DB::transaction(function () use ($variation) {
            $variation->delete();
        });

        $product->load('variations', 'images');

        return Response::json($this->getProductObject($product));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Product $product
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public
    function delete(Request $request, Business $business, Product $product)
    {
        Gate::inspect('manage', $business)->authorize();

        $productName = $product->name;

        ProductRepository::delete($product);

        Session::flash('success_message', 'The product \'' . $productName . '\' has been deleted successfully.');

        return Response::json([
            'redirect_url' => URL::route('dashboard.business.product.index', $business->getKey()),
        ]);
    }

    public
    function deleteProducts(Request $request, Business $business)
    {
        Gate::inspect('manage', $business)->authorize();

        foreach ($request->products as $product) {
            if (isset($product['checked']) && $product['checked']) {
                $product = Product::find($product['id']);
                ProductRepository::delete($product);
            }
        }

        $products = $business->products()->with('variations', 'images');

        switch ($request->status) {
            case 'draft':
                $products->whereNull('published_at');

                break;
            default:
                $products->whereNotNull('published_at');
        }

        $products = $products->orderByDesc('created_at')->get();

        $product_attrs = [];

        foreach ($products as $product) {
            $product_attrs['image'][] = $product->display('image');
            $product_attrs['price'][] = $product->display('price');
            $product_attrs['manageable'][] = $product->isManageable();
            $product_attrs['quantity'][] = $product->variations->sum('quantity');
        }

        return Response::json([
            'products' => $products,
            'product_attrs' => $product_attrs]);
    }

    private
    function getProductObject(Product $product)
    {
        if (!$product->shortcut_id) {
            $shortcut = $product->shortcut()->create([
                'route_name' => 'shop.product',
                'parameters' => [
                    'business' => $product->business_id,
                    'product_id' => $product->getKey(),
                ],
            ]);

            $product->shortcut_id = $shortcut->getKey();
            $product->save();
        }
        $data['id'] = $product->id;
        $data['name'] = $product->name;
        $data['description'] = $product->description;
        $data['currency'] = $product->currency;
        $data['price'] = $product->price;
        $data['stock_keeping_unit'] = $product->stock_keeping_unit;
        $data['categories'] = $product->business_product_category_id;
        $data['readable_price'] = $product->readable_price;
        $data['is_manageable'] = $product->quantity > 0;
        $data['is_published'] = $product->published_at instanceof Carbon;
        $data['has_variations'] = $product->variations_count > 1;
        $data['variations_count'] = $product->variations_count;
        $data['checkout_url'] = $product->shortcut_id
            ? URL::route('shortcut', $product->shortcut_id)
            : URL::route('shop.product', [
                $product->business_id,
                $product->getKey(),
            ]);
        if ($product->variations_count > 1) {
            $data['variation_types'] = array_filter([
                $product->variation_key_1,
                $product->variation_key_2,
                $product->variation_key_3,
            ]);
        } elseif ($data['is_manageable']) {
            $data['quantity'] = $product->variations[0]->quantity;
            $data['quantity_alert_level'] = $product->variations[0]->quantity_alert_level;
        }

        $data['variations'] = [];

        foreach ($product->variations as $variation) {
            $variationData = [
                'id' => $variation->id,
                'description' => $variation->description,
                'values' => [
                    [
                        'key' => $product->variation_key_1,
                        'value' => $variation->variation_value_1,
                    ],
                    [
                        'key' => $product->variation_key_2,
                        'value' => $variation->variation_value_2,
                    ],
                    [
                        'key' => $product->variation_key_3,
                        'value' => $variation->variation_value_3,
                    ],
                ],
                'price' => getReadableAmountByCurrency($product->currency, $variation->price),
            ];

            if ($data['is_manageable']) {
                $variationData['quantity'] = $variation->quantity;
                $variationData['quantity_alert_level'] = $variation->quantity_alert_level;
            }

            $data['variations'][] = $variationData;
        }

        if ($product->relationLoaded('images')) {
            foreach ($product->images as $image) {
                $data['image'][] = [
                    'id' => $image->getKey(),
                    'url' => $image->getUrl(),
                ];
            }
        }

        $data['is_pinned'] = $product->is_pinned;
        $data['created_at'] = $product->created_at->toAtomString();
        $data['updated_at'] = $product->updated_at->toAtomString();

        return $data;
    }

    /**
     * @param Business $business
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public
    function createInBulk(Business $business)
    {
        Gate::inspect('manage', $business)->authorize();
        return Response::view('dashboard.business.product.bulk', compact('business'));
    }

    public
    function downloadFeedTemplate(Request $request, Business $business)
    {
        Gate::inspect('manage', $business)->authorize();
        $fileName = config('app.name') . "-product-feed.csv";
        return Excel::download(new ProductFeedTemplate, $fileName, \Maatwebsite\Excel\Excel::CSV)->send();
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return JsonResponse
     */
    public
    function uploadFeedFile(Request $request, Business $business)
    {
        $request->validate([
            'file' => 'required',
        ]);
        $folderName = $business->getKey() . '/product-feed-templates';
        $fileName = $business->getKey() . '-' . time() . '-product_feed_template.csv';
        $path = $request->file('file')->storeAs($folderName, $fileName);

        Artisan::queue('proceed:productFeed --business_id=' . $business->getKey() . ' --file_path=' . $path);

        Session::flash('success_message', 'We  will start to upload shortly and email you the result.');
        return Response::json([
            'redirect_url' => URL::route('dashboard.business.product.index', $business->getKey()),
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function export(Request $request, Business $business)
    {
        Gate::inspect('operate', $business)->authorize();

        $data = $request->validate([
            'export_option' => 'required',
            'inventory' => 'nullable|int|min:0',
            'before_date' => 'nullable|date_format:Y-m-d'
        ]);

        $products = $business->products()->with('variations', 'images');

        if ($data['export_option'] === 'created_before') {
            $products->where('created_at', '<', Date::parse($data['before_date']));
        }

        $products = $products->orderByDesc('created_at')->limit(1000)->get();

        if ($data['export_option'] === 'inventory') {
            $inventory = $data['inventory'];
            $products = $products->map(function ($product) use ($inventory) {
                if ($product->variations->sum('quantity') < $inventory) {
                    $product->image_display = $product->display('image');
                    $product->price_display = $product->display('price');
                    $product->manageable = $product->isManageable();
                    $product->variations_sum_quantity = $product->variations->sum('quantity');
                    $product->published = $product->isPublished();
                    return $product;
                }
            })->reject(null);
        }

        if ($products->count() < 1) {
            App::abort(422, 'You don\'t have any products based on the parameters.');
        }

        SendExportedProducts::dispatch($business, $products, Auth::user());

        return Response::json([
            'success' => true,
        ]);
    }
}
