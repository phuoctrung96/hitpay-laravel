<?php
declare(strict_types=1);

namespace App\Services\Shopify;

use App\Business;
use App\BusinessShopifyStore;
use App\Services\Shopify\Api\Payment\PaymentsAppConfigureApi;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Class ShopifyApp
 * @package App\Services\Shopify
 */
class ShopifyApp
{
    protected Business $business;

    public function __construct(Business $business)
    {
        $this->business = $business;
    }

    /***
     * @param Business $business
     * @param $shopifyUser
     * @return string
     * @throws \Exception
     */
    public static function installApp(Business $business, $shopifyUser) : string
    {
        $businessShopifyStore = BusinessShopifyStore::create([
            'business_id' => $business->getKey(),
            'shopify_id' => $shopifyUser->getId(),
            'shopify_name' => $shopifyUser->getName(),
            'shopify_domain' => $shopifyUser->getNickname(),
            'shopify_token' => $shopifyUser->token,
            'shopify_data' => json_encode($shopifyUser)
        ]);

        try {
            Log::info('shop token check 1: ' . $shopifyUser->token);

            $api = new PaymentsAppConfigureApi();
            $api->setToken($shopifyUser->token);
            $api->setReady(true);
            $api->setExternalHandler($business->getKey());

            $url = "https://" . $shopifyUser->getNickname() . '/payments_apps/api/2021-10/graphql.json';

            $api->setUrl($url);

            $api->handle();

            return "https://" . $shopifyUser->getNickname() . "/services/payments_partners/gateways/" . Config::get('services.shopify.client_id_v2') . "/settings";
        } catch (\Exception $exception) {
            $businessShopifyStore->delete();

            throw $exception;
        }
    }

    /***
     * @param BusinessShopifyStore $businessShopifyStore
     * @return bool
     * @throws \Exception
     */
    public function uninstallApp(BusinessShopifyStore $businessShopifyStore)
    {
        $api = new PaymentsAppConfigureApi();
        $api->setToken($businessShopifyStore->shopify_token);
        $api->setReady(false);
        $api->setExternalHandler($businessShopifyStore->id);

        $url = "https://" . $businessShopifyStore->shopify_domain . '/payments_apps/api/2021-10/graphql.json';

        $api->setUrl($url);

        $status = $api->handle();

        if ($status) {
            throw new \Exception("Uninstall payment app failed.");
        }

        // delete business shopify store
        $businessShopifyStore->delete();

        return true;
    }
}
