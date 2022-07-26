<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Enumerations\AllCountryCode;
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
use App\Manager\BusinessManagerInterface;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Collection;
use App\Enumerations\Business\ShippingCalculation;

class ShopDashboardController extends Controller
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
     * Shop dashboard
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request, Business $business, BusinessManagerInterface $businessManager)
    {
        Gate::inspect('view', $business)->authorize();

        $customisation = $business->checkoutCustomisation();

        $customisation->all_methods = array_keys($businessManager->getByBusinessAvailablePaymentMethods($business, null, true));

        if (!$customisation->payment_order) {
            $customisation->payment_order = $customisation->all_methods;
        }

        $random = '43420024420024';
        $shop_url = str_replace($random, '', URL::route('shop.business', $random));

        $data = $this->formData($business);

        return Response::view('dashboard.business.dashboard.index', compact('business', 'customisation', 'shop_url', 'data'));
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     */
    private function formData(Business $business)
    {
        $countries = new Collection;

        foreach (AllCountryCode::listConstants() as $value) {
            if (Lang::has('misc.country.' . $value)) {
                $name = Lang::get('misc.country.' . $value);
            } else {
                $name = $value;
            }

            $countries->add([
                'code' => $value,
                'name' => $name,
            ]);
        }

        $data['countries'][] = [
            'code' => 'global',
            'name' => 'Global',
        ];
        $data['countries'] = array_merge($data['countries'], $countries->sortBy('name')->values()->toArray());

        $data['calculations'] = [
            [
                'code' => ShippingCalculation::FLAT,
                'name' => Lang::get('misc.shipping_calculation.' . ShippingCalculation::FLAT),
            ],
            [
                'code' => ShippingCalculation::FEE_PER_UNIT,
                'name' => Lang::get('misc.shipping_calculation.' . ShippingCalculation::FEE_PER_UNIT),
            ],
        ];

        $data['categories'] = $business->productCategories()->where('active', 1)->get();

        return $data;
    }

    /**
     * Shop Insight
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function insight(Request $request, Business $business, BusinessManagerInterface $businessManager)
    {
        Gate::inspect('view', $business)->authorize();
        return Response::view('dashboard.business.insight.index', compact('business'));
    }

}
