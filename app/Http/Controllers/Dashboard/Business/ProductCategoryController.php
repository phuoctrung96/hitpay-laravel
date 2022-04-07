<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Business\Image;
use App\Business\Product;
use App\Business\ProductVariation;
use App\Enumerations\Business\ImageGroup;
use App\Enumerations\CountryCode;
use App\Enumerations\CurrencyCode;
use App\Exports\ProductFeedTemplate;
use App\Http\Controllers\Controller;
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

class ProductCategoryController extends Controller
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

        $productsCategories = $business->productCategories()->paginate(25);

        return Response::view('dashboard.business.product-categories.index', compact('business', 'productsCategories'));
    }

    /**
     * Show product category create form.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(Business $business)
    {
        Gate::inspect('manage', $business)->authorize();

        return Response::view('dashboard.business.product-categories.create', compact('business'));
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\JsonResponse
     * @throws ValidationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function store(Request $request, Business $business)
    {
        Gate::inspect('manage', $business)->authorize();
        $categoryID = $request->get('id');

        $requestData = $this->validate($request, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'active' => 'required'
            ]);
        try {

            DB::beginTransaction();
            if(isset($categoryID))
            {
                $business->productCategories()->where('id', $categoryID)->update($requestData);
            }
            else {
                $business->productCategories()->create($requestData);
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
        Session::flash('success_message', !isset($categoryID)? 'The product category has been created.'
        :'Successfully updated');
        return Response::json([
            'redirect_url' => URL::route('dashboard.business.product-categories.index', [
                $business->getKey(),
            ]),
        ]);
    }
    public function edit(Business $business, Business\ProductCategory $category)
    {
        if (!isset($category->id))
        {
            App::abort(404);
        }
        return Response::view('dashboard.business.product-categories.edit', compact('business', 'category'));
    }
    public function delete(Business $business, Business\ProductCategory $category)
    {
        if (!isset($category->id))
        {
            App::abort(404);
        }
        try {
            $category->delete();
        }
        catch (\Exception $exception)
        {
            throw $exception;
        }
        Session::flash('success_message', 'Successfully deleted');
        return redirect()->back();
    }
}
