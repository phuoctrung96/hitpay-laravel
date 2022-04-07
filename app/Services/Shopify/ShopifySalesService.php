<?php
declare(strict_types=1);

namespace App\Services\Shopify;

use App\Business;
use App\BusinessShopifyPayment;
use App\Exceptions\ShopifyCheckoutException;
use App\Services\Shopify\Api\Payment\PaymentSessionResolveApi;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Class ShopifySalesService
 * @package App\Services\Shopify
 */
class ShopifySalesService
{
    private Business $business;

    public function __construct(Business $business)
    {
        $this->business = $business;
    }

    /**
     * @param string $invoiceId
     * @return bool
     * @throws ShopifyCheckoutException
     * @throws \Exception
     */
    public function markInvoiceAsPaid(string $invoiceId)
    {
        $businessShopifyPayment = BusinessShopifyPayment::where('invoice_id', $invoiceId)
            ->where('business_id', $this->business->id)->first();

        if (!$businessShopifyPayment) {
            throw new \Exception("Payment not found with invoice_id " .$invoiceId, 500);
        }

        // call payment session resolve api
        $expiredPaymentDate = null; // TODO check mean setAuthrizationExpiredAt;

        $paymentData = json_decode(json_encode($businessShopifyPayment->request_data)); // request data has cast to array

        if ($paymentData == "") {
            throw new \Exception("Empty payment data", 500);
        }

        if ($paymentData->shopify_api_version == "") {
            throw new \Exception("Empty shopify_api_version", 500);
        }

        if ($paymentData->shopify_domain == "") {
            throw new \Exception("Empty shopify_domain", 500);
        }

        if ($paymentData->shopify_token == "") {
            throw new \Exception("Empty shopify_token", 500);
        }

        $realShopDomain = $paymentData->shopify_domain_real;

        $url = "https://".$realShopDomain."/payments_apps/api/".$paymentData->shopify_api_version."/graphql.json";

        if ($paymentData->shopify_token_real === $paymentData->shopify_token) {
            $shopifyToken = $paymentData->shopify_token_real;
        } else {
            $isTestMode = $paymentData->test;

            if (Config::get('services.shopify.business_test_sandbox_to_staging')) {
                if ($isTestMode && App::environment('staging')) {
                    $shopifyToken = $paymentData->shopify_token_real;
                } else {
                    $shopifyToken = $paymentData->shopify_token;
                }
            } else {
                if ($isTestMode && App::environment('sandbox')) {
                    $shopifyToken = $paymentData->shopify_token_real;  // production to sandbox
                } else {
                    $shopifyToken = $paymentData->shopify_token;
                }
            }
        }

        $paymentSessionResolveApi = new PaymentSessionResolveApi();
        $paymentSessionResolveApi->setToken($shopifyToken);
        $paymentSessionResolveApi->setId($businessShopifyPayment->gid);
        $paymentSessionResolveApi->setAuthorizationExpiresAt($expiredPaymentDate);
        $paymentSessionResolveApi->setUrl($url);
        $resolvePaymentApiResponse = $paymentSessionResolveApi->handle();

        $businessShopifyPayment->data = $resolvePaymentApiResponse;
        $businessShopifyPayment->save();

        return true;
    }
}
