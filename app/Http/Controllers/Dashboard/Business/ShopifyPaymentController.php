<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\BusinessShopifyStore;
use App\Http\Controllers\Controller;
use App\Services\Shopify\ShopifyApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;

class ShopifyPaymentController extends Controller
{
    /**
     * ShopifyController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->businessesOwned->count() <= 0) {
            // TODO add intent url here
            return Response::redirectToRoute('dashboard.business.create', [
                'src' => 'shopify-app',
            ]);
        } elseif ($user->businessesOwned->count() > 1) {
            App::abort(403, 'Wow, your account is having multiple businesses, please contact us.');
        }

        return Response::redirectToRoute('dashboard.business.payment.integration.shopify.authorize', [
            'business_id' => $user->businessesOwned->first()->getKey(),
        ] + $request->all());
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function authorizeAccount(Request $request, Business $business)
    {
        $this->hmacCheck($request);

        Gate::inspect('update', $business)->authorize();

        $sessionShopifyUser = $request->session()->get('shopify_app_manage_' . $request->get('shop'));

        if ($sessionShopifyUser == "") {
            throw new \Exception("Have issue on shopify user null");
        }

        $shopifyUser = $sessionShopifyUser['shopifyUser'];

        $businessShopifyStores = $business->shopifyStores()->get();

        $shopifyName = $shopifyUser->name;

        $shopifyDomain = $shopifyUser->nickname;

        $newAuthenticated = true;

        if ($businessShopifyStores->count() > 0 && in_array($shopifyDomain, $businessShopifyStores->pluck('shopify_domain')->toArray())) {
            $newAuthenticated = false;
        }

        $shopifyAdminLink = "https://" . $shopifyDomain . "/admin";

        if (!$newAuthenticated) {
            // existing shop
            $shopifyMerchantUrlRedirection = "https://" . $shopifyDomain . "/services/payments_partners/gateways/" . Config::get('services.shopify.client_id_v2') . "/settings";

            return Response::view('dashboard.business.shopify.payment-integration.confirmation-exists', [
                'shopifyMerchantUrlRedirection' => $shopifyMerchantUrlRedirection,
                'shopifyName' => $shopifyName,
                'shopifyDomain' => $shopifyDomain,
                'business' => $business,
            ]);
        }

        // check there is any business connect with shopify domain
        $checkAvailability = BusinessShopifyStore::where('shopify_domain', $shopifyDomain)->first();

        if ($checkAvailability) {
            $oldBusiness = $checkAvailability->business;

            return Response::view('dashboard.business.shopify.payment-integration.confirmation-duplicate', [
                'shopifyName' => $shopifyName,
                'shopifyDomain' => $shopifyDomain,
                'business' => $business,
                'oldBusiness' => $oldBusiness
            ]);
        }

        $queryParams = http_build_query($request->query());

        $continueLink = route('dashboard.business.payment.integration.shopify.confirm', [
            'business_id' => $business->getKey(),
        ]); // confirm install app

        $continueLink .= "?" . $queryParams;

        $cancelLink = $shopifyAdminLink; // back to shopify admin

        $request->session()->put('shopify_app', [
            'shopifyUser' => $shopifyUser
        ]);

        if ($businessShopifyStores->count() > 0) {
            // business have 1 store of shopify

            if ($businessShopifyStores->count() >= BusinessShopifyStore::MAX_STORES) {
                return Response::view('dashboard.business.shopify.payment-integration.confirmation-blocked', [
                    'shopifyName' => $shopifyName,
                    'shopifyDomain' => $shopifyDomain,
                    'business' => $business,
                    'shopifyAdminLink' => $shopifyAdminLink
                ]);
            } else {
                $oldShopDomains = $businessShopifyStores->pluck('shopify_domain')->toArray();

                return Response::view('dashboard.business.shopify.payment-integration.confirmation-multiple', [
                    'shopifyName' => $shopifyName,
                    'shopifyDomain' => $shopifyDomain,
                    'business' => $business,
                    'continueLink' => $continueLink,
                    'cancelLink' => $cancelLink,
                    'oldShopDomains' => $oldShopDomains
                ]);
            }
        }

        // business no have store of shopify and want connect
        return Response::view('dashboard.business.shopify.payment-integration.confirmation-new', [
            'shopifyName' => $shopifyName,
            'shopifyDomain' => $shopifyDomain,
            'business' => $business,
            'continueLink' => $continueLink,
            'cancelLink' => $cancelLink
        ]);
    }

    /***
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function confirm(Request $request, Business $business): \Illuminate\Http\RedirectResponse
    {
        $this->hmacCheck($request);

        $shopifyApp = $request->session()->pull('shopify_app');

        if (!$shopifyApp) {
            Log::critical("There is issue when confirming shopify flow with session null data with request:" . json_encode($request->all()));

            return Redirect::route('dashboard.home');
        }

        $shopifyUser = $shopifyApp['shopifyUser'];

        // install app
        $urlCallback = ShopifyApp::installApp($business, $shopifyUser);

        // setting up iframe protection
        // https://shopify.dev/apps/store/security/iframe-protection
        $headers = [
            'Content-Security-Policy' => "frame-ancestors https://{$shopifyUser->getNickname()} https://admin.shopify.com"
        ];

        return Redirect::to($urlCallback)->withHeaders($headers);
    }

    /**
     * Check the request returned from Shopify.
     *
     * @param \Illuminate\Http\Request $request
     */
    private function hmacCheck(Request $request) : void
    {
        if (!$request->has('shop', 'hmac', 'timestamp')) {
            App::abort(404);
        }

        foreach ($request->query() as $parameter => $value) {
            if ($parameter === 'hmac') {
                continue;
            }

            $parameters[$parameter] = $parameter.'='.$value;
        }

        if (!isset($parameters)) {
            App::abort(404);
        } else {
            asort($parameters);

            $parameters = implode('&', $parameters);
            $hitPayHmac = hash_hmac('sha256', $parameters, Config::get('services.shopify.client_secret_v2'));

            if ($request->get('hmac') !== $hitPayHmac) {
                App::abort(404);
            }
        }
    }
}

