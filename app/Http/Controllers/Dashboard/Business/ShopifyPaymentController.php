<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\BusinessShopifyStore;
use App\Http\Controllers\Controller;
use App\Services\Shopify\ShopifyApp;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\Config as SocialiteConfig;

class ShopifyPaymentController extends Controller
{
    /**
     * ShopifyController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Business $business)
    {
        abort(404);
    }

    /**
     * Select business for authentication
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function selectBusiness(Request $request)
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

        return Response::redirectToRoute('dashboard.business.payment.integration.shopify.redirect', [
                'business_id' => $user->businessesOwned->first()->getKey(),
            ] + $request->all());
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function redirect(Request $request, Business $business)
    {
        Gate::inspect('update', $business)->authorize();

        $this->hmacCheck($request);

        $domain = $request->get('shop');

        $shopify = '.myshopify.com';

        if (Str::endsWith($domain, $shopify)) {
            $domain = str_replace($shopify, '', $domain);
        }

        $config = new SocialiteConfig(
            Config::get('services.shopify.client_id_v2'),
            Config::get('services.shopify.client_secret_v2'),
            URL::route('dashboard.payment.integration.shopify.authorize'),
            [
                'subdomain' => $domain,
            ]
        );

        $request->session()->put('shopify_app', [
            'shopify_domain' => $domain,
            'business_id' => $business->getKey(),
        ]);

        return Socialite::with('shopify')->setConfig($config)->scopes([
            'write_payment_gateways',
            'write_payment_sessions',
            'read_payment_gateways',
            'read_payment_sessions',
        ])->redirect();
    }

    /**
     * Redirect the authorization to the actual path.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function doAuthorizationRedirection(Request $request)
    {
        $this->hmacCheck($request);

        $shopifyApp = $request->session()->pull('shopify_app');

        if ($shopifyApp['shopify_domain'].'.myshopify.com' !== $request->get('shop')) {
            App::abort(404);
        }

        return Response::redirectToRoute('dashboard.business.payment.integration.shopify.authorize', [
                'business_id' => $shopifyApp['business_id'],
            ] + $request->all());
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeAccount(Request $request, Business $business)
    {
        $this->hmacCheck($request);

        Gate::inspect('update', $business)->authorize();

        try {
            $clientId = Config::get('services.shopify.client_id_v2');
            $clientSecret = Config::get('services.shopify.client_secret_v2');
            $config = new \SocialiteProviders\Manager\Config($clientId, $clientSecret, '');

            /**
             * @var \SocialiteProviders\Manager\OAuth2\User $shopifyUser
             */
            $shopifyUser = Socialite::driver('shopify')->setConfig($config)->user();
        } catch (ClientException $exception) {
            $body = json_decode($exception->getResponse()->getBody()->getContents(), true);

            if ($body !== false && $body['error'] === 'invalid_request') {
                return Response::redirectToRoute('dashboard.business.payment.integration.shopify.redirect', [
                        'business_id' => $business->getKey(),
                    ] + $request->all());
            }

            throw $exception;
        } catch (InvalidStateException $exception) {
            return Response::redirectToRoute('dashboard.business.payment.integration.shopify.redirect', [
                'business_id' => $business->getKey(),
            ]);
        }

        $businessShopifyStores = $business->shopifyStores()->get();

        $shopifyName = $shopifyUser->getName();
        $shopifyDomain = $shopifyUser->getNickname();

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
    public function confirm(Request $request, Business $business)
    {
        $this->hmacCheck($request);

        $shopifyApp = $request->session()->pull('shopify_app');

        $shopifyUser = $shopifyApp['shopifyUser'];

        // install app
        $urlCallback = ShopifyApp::installApp($business, $shopifyUser);

        return Redirect::to($urlCallback);
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
            $hitpayHmac = hash_hmac('sha256', $parameters, Config::get('services.shopify.client_secret_v2'));

            if ($request->get('hmac') !== $hitpayHmac) {
                App::abort(404);
            }
        }
    }
}

