<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Http\Controllers\Controller;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\Config as SocialiteConfig;

class ShopifyOauthController extends Controller
{
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
     * @return mixed
     */
    public function redirect(Request $request)
    {
        $this->hmacCheck($request);

        $domain = $request->get('shop');

        if ($request->session()->get('shopify_app_' . $domain)) {
            // this state user already install the app on shopify
            // then user want to manage to their HitPay business
            $sessionShopifyApp = $request->session()->get('shopify_app_' . $domain);

            $request->session()->put('shopify_app_manage_' . $domain, [
                'shopifyUser' => $sessionShopifyApp['shopifyUser']
            ]);

            $request->session()->remove('shopify_app_' . $domain);

            // redirect to login
            return Response::redirectToRoute('dashboard.payment.integration.shopify.index',
                [] + $request->all()
            );
        } else {
            // do oauth flow
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

            return Socialite::with('shopify')->setConfig($config)->scopes([
                'write_payment_gateways',
                'write_payment_sessions',
                'read_payment_gateways',
                'read_payment_sessions',
            ])->redirect();
        }
    }

    /**
     * @param Request $request
     * @return void
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
            $hmac = hash_hmac('sha256', $parameters, Config::get('services.shopify.client_secret_v2'));

            if ($request->get('hmac') !== $hmac) {
                App::abort(404);
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function doAuthorizationRedirection(Request $request)
    {
        $request->session()->remove('shopify_app_' . $request->get('shop'));

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
                Log::critical("Have issue when installing shopify app with issue: " . $exception->getMessage());
            }

            throw $exception;
        } catch (InvalidStateException $exception) {
            Log::critical("[Shopify InvalidStateException] There is issue when user want to authorize \n
                this shop {$request->get('shop')} \n
                with params " . json_encode($request->all()) . " \n
                and message error tracing " . $exception->getTraceAsString());

            return Response::redirectToRoute('dashboard.payment.integration.shopify.invalid.state', $request->all());
        }

        $request->session()->put('shopify_app_' . $shopifyUser->nickname, [
            'shopifyUser' => $shopifyUser
        ]);

        $redirectUrl = "https://{$shopifyUser->nickname}/services/payments_partners/gateways/" . Config::get('services.shopify.client_id_v2') . "/settings";

        // setting up iframe protection
        // https://shopify.dev/apps/store/security/iframe-protection
        $headers = [
            'Content-Security-Policy' => "frame-ancestors https://{$shopifyUser->nickname} https://admin.shopify.com"
        ];

        return redirect($redirectUrl)->withHeaders($headers);
    }

    public function invalidState(Request $request)
    {
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

        $url = Socialite::with('shopify')->setConfig($config)->scopes([
            'write_payment_gateways',
            'write_payment_sessions',
            'read_payment_gateways',
            'read_payment_sessions',
        ])->redirect()->getTargetUrl();

        return Response::view('dashboard.shopify.invalidstate', compact("url"));
    }
}

