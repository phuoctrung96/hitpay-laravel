<?php

namespace App\Http\Controllers\Dashboard\Business\Shopify;

use App\Http\Controllers\Controller;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\Config as SocialiteConfig;

class AuthenticationController extends Controller
{
    /**
     * AuthenticationController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function processRedirection(Request $request)
    {
        if (Auth::check()) {
            /** @var \App\User $user */
            $user = Auth::user();

            if ($user->shopify_id) {
                return Response::view('merchant.shopify.message', [
                    'page_title' => 'Shopify Account Connected',
                    'message' => 'You have already connected to \''.$user->shopify_domain.'\'. '
                        .'If you want to stop using this app or want to connect to another shopify account, you may uninstall the app from your Shopify dashboard.',
                    'redirect' => [
                        'url' => 'https://'.$user->shopify_domain.'/admin',
                        'name' => 'Go to Shopify',
                    ],
                ]);
            }
        }

        if (!$request->has('shop')) {
            abort(404);
        }

        $domain = $request->get('shop');

        $shopify = '.myshopify.com';

        if (Str::endsWith($domain, $shopify)) {
            $domain = str_replace($shopify, '', $domain);
        }

        $config = new SocialiteConfig(
            Config::get('services.shopify.client_id'),
            Config::get('services.shopify.client_secret'),
            Config::get('services.shopify.redirect'),
            [
                'subdomain' => $domain,
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
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \Exception
     */
    public function processCallback(Request $request)
    {
        /** @var \App\User $user */
        $user = Auth::user();

        try {
            /** @var \SocialiteProviders\Manager\OAuth2\User $shopifyUser */
            $shopifyUser = Socialite::driver('shopify')->user();

            if (strtoupper($shopifyUser->user['currency']) !== strtoupper($user->default_currency_code)) {
                // todo throw error
                dd('Unmatched currency.');
            }
        } catch (Exception $exception) {
            switch (true) {
                case $exception instanceof ClientException:
                case $exception instanceof InvalidStateException:
                    return Response::redirectToRoute('merchant.shopify.auth')
                        ->with('shopify_error', 'Timeout. Please try again.');
            }

            throw $exception;
        }

        if ($user->shopify_id) {
            if ($user->shopify_id === $shopifyUser->id) {
                $user->shopify_token = $shopifyUser->token;

                $user->save();
            }

            // todo we should ask if different account detected

            return Response::view('merchant.shopify.message', [
                'page_title' => 'Shopify Account Connected',
                'message' => 'You have already connected to \''.$user->shopify_domain
                    .'\'. If you want to stop using this app or want to connect to another shopify account, you may uninstall the app from your Shopify dashboard.',
                'redirect' => [
                    'url' => 'https://'.$user->shopify_domain.'/admin',
                    'name' => 'Go to Shopify',
                ],
            ]);
        }

        $user->shopify_domain = $shopifyUser->user['myshopify_domain'] ?? null;
        $user->shopify_id = $shopifyUser->id;
        $user->shopify_token = $shopifyUser->token;

        $extraData = $user->extra_data;

        $extraData['shopify']['shop'] = $shopifyUser->user;

        $user->extra_data = $extraData;

        $user->save();

        dispatch(new RegisterShopifyWebhook($user));

        $shopifyAccount = Shopify::retrieve($user->shopify_domain, $user->shopify_token);
        $locations = $shopifyAccount->get('locations');

        if (isset($locations['locations']) && is_array($locations['locations'])
            && count($locations['locations']) === 1) {
            $user->shopify_location_id = $locations['locations'][0]['id'];

            $user->save();

            return Response::redirectToRoute('merchant.shopify.product');
        }

        return Response::redirectToRoute('merchant.shopify.location');
    }
}
