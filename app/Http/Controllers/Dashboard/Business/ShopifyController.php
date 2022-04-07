<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Http\Controllers\Controller;
use App\Jobs\Business\Shopify\RegisterWebhooks;
use App\Jobs\Business\Shopify\SyncProducts;
use GuzzleHttp\Exception\ClientException;
use HitPay\Shopify\Shopify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\Config as SocialiteConfig;

class ShopifyController extends Controller
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
    public function showHomepage(Business $business)
    {
        abort(404);

        Gate::inspect('update', $business)->authorize();

        return Response::view('dashboard.business.shopify.home', compact('business'));
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

        return Response::redirectToRoute('dashboard.business.integration.shopify.redirect', [
                'business_id' => $user->businessesOwned->first()->getKey(),
            ] + $request->all());
    }

    /**
     * Redirect to Shopify to authorize HitPay request.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function doRedirection(Request $request, Business $business)
    {
        Gate::inspect('update', $business)->authorize();

        $this->hmacCheck($request);

        $domain = $request->get('shop');

        $shopify = '.myshopify.com';

        if (Str::endsWith($domain, $shopify)) {
            $domain = str_replace($shopify, '', $domain);
        }

        $config = new SocialiteConfig(
            Config::get('services.shopify.client_id'),
            Config::get('services.shopify.client_secret'),
            URL::route('dashboard.integration.shopify.authorize'),
            [
                'subdomain' => $domain,
            ]);

        $request->session()->put('shopify_app', [
            'shopify_domain' => $domain,
            'business_id' => $business->getKey(),
        ]);

        return Socialite::with('shopify')->setConfig($config)->scopes([
            'read_locations',
            'read_inventory',
            'read_products',
            'read_product_listings',
            'write_inventory',
            'write_products',
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

        return Response::redirectToRoute('dashboard.business.integration.shopify.authorize', [
                'business_id' => $shopifyApp['business_id'],
            ] + $request->all());
    }

    /**
     * Authorize Shopify account.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business\PaymentProvider|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function authorizeAccount(Request $request, Business $business)
    {
        abort(404);

        $this->hmacCheck($request);

        Gate::inspect('update', $business)->authorize();

        try {
            /**
             * @var \SocialiteProviders\Manager\OAuth2\User $shopifyUser
             */
            $shopifyUser = Socialite::driver('shopify')->user();
        } catch (ClientException $exception) {
            $body = json_decode($exception->getResponse()->getBody()->getContents(), true);

            if ($body !== false && $body['error'] === 'invalid_request') {
                return Response::redirectToRoute('dashboard.business.integration.shopify.redirect', [
                        'business_id' => $business->getKey(),
                    ] + $request->all());
            }

            throw $exception;
        } catch (InvalidStateException $exception) {
            return Response::redirectToRoute('dashboard.business.integration.shopify.redirect', [
                'business_id' => $business->getKey(),
            ]);
        }

        $newAuthenticated = $business->shopify_id !== $shopifyUser->id;

        $business->shopify_id = $shopifyUser->getId();
        $business->shopify_name = $shopifyUser->getName();
        $business->shopify_domain = $shopifyUser->getNickname();
        $business->shopify_token = $shopifyUser->token;
        $business->shopify_currency = $shopifyUser->user['currency'];

        $shopifyData = $newAuthenticated ? [] : $business->shopify_data;
        $shopifyData['shop'] = $shopifyUser->user;

        $business->shopify_data = $shopifyData;

        // todo use transaction
        $business->save();

        if ($newAuthenticated) {
            dispatch(new RegisterWebhooks($business));

            $shopify = new Shopify($business->shopify_domain, $business->shopify_token);
            $locations = $shopify->locations();

            if (count($locations['locations']) === 1) {
                $business->shopify_location_id = $locations['locations'][0]['id'];
                $shopifyData = $business->shopify_data;
                $shopifyData['location'] = $locations['locations'][0];
                $business->shopify_data = $shopifyData;
                $business->save();

                return Response::redirectToRoute('dashboard.business.integration.shopify.setting.product',
                    $business->getKey());
            }
        }

        return Response::redirectToRoute('dashboard.business.integration.shopify.setting.location',
            $business->getKey());
    }

    /**
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showLocationSettingPage(Business $business)
    {
        Gate::inspect('update', $business)->authorize();

        if (!$business->shopify_id) {
            return Response::redirectToRoute('dashboard.business.integration.shopify.home', $business->getKey());
        }

        $shopify = new Shopify($business->shopify_domain, $business->shopify_token);
        $locations = $shopify->locations();

        if (count($locations['locations']) === 1) {
            $business->shopify_location_id = $locations['locations'][0]['id'];
            $shopifyData = $business->shopify_data;
            $shopifyData['location'] = $locations['locations'][0];
            $business->shopify_data = $shopifyData;
            $business->save();

            return Response::redirectToRoute('dashboard.business.integration.shopify.setting.product',
                $business->getKey());
        }

        return Response::view('dashboard.business.shopify.location', compact('business', 'locations'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function setLocation(Request $request, Business $business)
    {
        Gate::inspect('update', $business)->authorize();

        if ($business->shopify_location_id) {
            App::abort(403, 'Our platform is currently not allow a business to change it\'s location ID. To change it,'
                .' remove HitPay app from Shopify admin and install again.');
        }

        $shopify = new Shopify($business->shopify_domain, $business->shopify_token);
        $locations = $shopify->locations();
        $locations = collect($locations['locations']);

        $data = $this->validate($request, [
            'location_id' => [
                'required',
                Rule::in($locations->pluck('id')),
            ],
        ]);

        // todo use transaction
        $business->shopify_location_id = $data['location_id'];
        $shopifyData = $business->shopify_data;
        $shopifyData['location'] = $locations->where('id', $data['location_id'])->first();
        $business->shopify_data = $shopifyData;
        $business->save();

        return Response::redirectToRoute('dashboard.business.integration.shopify.setting.product', $business->getKey());
    }

    /**
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showProductSyncPage(Business $business)
    {
        Gate::inspect('update', $business)->authorize();

        if (Cache::has('business_'.$business->getKey().'_shopify_syncing')) {
            return Response::view('dashboard.business.shopify.product-syncing', compact('business'));
        }

        if (!$business->shopify_location_id) {
            return Response::redirectToRoute('dashboard.business.integration.shopify.setting.location',
                $business->getKey());
        }

        $shopify = new Shopify($business->shopify_domain, $business->shopify_token);

        return Response::view('dashboard.business.shopify.product', [
            'business' => $business,
            'number_of_shopify_products' => $shopify->countProducts()['count'],
            'number_of_shopify_products_in_hitpay' => $business->products()->whereNotNull('shopify_id')->count(),
        ]);
    }

    /**
     * Sync products from Shopify.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function syncProduct(Request $request, Business $business)
    {
        Gate::inspect('update', $business)->authorize();

        dispatch(new SyncProducts($business))->onQueue('long-tasks');

        $shopify = new Shopify($business->shopify_domain, $business->shopify_token);

        Cache::add('business_'.$business->getKey().'_shopify_syncing', $shopify->countProducts()['count']);
        Cache::add('business_'.$business->getKey().'_shopify_synced', 0);

        return Response::redirectToRoute('dashboard.business.integration.shopify.setting.product',
            $business->getKey());
    }

    public function getProductSyncProgress(Business $business)
    {
        Gate::inspect('update', $business)->authorize();

        if (Cache::has('business_'.$business->getKey().'_shopify_syncing')) {
            $total = Cache::get('business_'.$business->getKey().'_shopify_syncing');
            $current = Cache::get('business_'.$business->getKey().'_shopify_synced');
        } else {
            $current = $total = 1;
        }

        return Response::json([
            'current' => $current,
            'total' => $total,
            'percentage' => (int) bcmul(bcdiv($current, $total, 4), '100', 0),
        ]);
    }

    /**
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unauthorize(Business $business)
    {
        Gate::inspect('update', $business)->authorize();

        Cache::forget('business_'.$business->getKey().'_shopify_syncing');
        Cache::forget('business_'.$business->getKey().'_shopify_synced');

        if ($business->shopify_id) {
            $shopify = new Shopify($business->shopify_domain, $business->shopify_token);
            $shopify->uninstall();

            $business->productBases()->whereNotNull('shopify_id')->forceDelete();

            // todo, use transaction

            $business->shopify_data = null;
            $business->shopify_id = null;
            $business->shopify_domain = null;
            $business->shopify_token = null;
            $business->shopify_location_id = null;

            $business->save();
        }

        return Response::redirectToRoute('dashboard.business.integration.shopify.home', $business->getKey());
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
            $hitpayHmac = hash_hmac('sha256', $parameters, Config::get('services.shopify.client_secret'));

            if ($request->get('hmac') !== $hitpayHmac) {
                App::abort(404);
            }
        }
    }
}
