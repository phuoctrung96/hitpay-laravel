<?php
declare(strict_types=1);

namespace App\Services\Shopify;

use App\Business;
use App\BusinessShopifyPayment;
use App\Exceptions\ShopifyCheckoutException;
use App\Services\Shopify\Api\Payment\PaymentSessionResolveApi;
use GuzzleHttp\Exception\ServerException;
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
        try {
            $businessShopifyPayment = BusinessShopifyPayment::where('invoice_id', $invoiceId)
                ->where('business_id', $this->business->id)->first();

            if (!$businessShopifyPayment) {
                throw new \Exception("Payment not found with invoice_id " .$invoiceId, 500);
            }

            // call payment session resolve api
            $expiredPaymentDate = null; // TODO check mean setAuthrizationExpiredAt;

            $paymentData = json_decode($businessShopifyPayment->request_data);

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

            $url = "https://".$paymentData->shopify_domain."/payments_apps/api/".$paymentData->shopify_api_version."/graphql.json";

            $paymentSessionResolveApi = new PaymentSessionResolveApi();
            $paymentSessionResolveApi->setToken($paymentData->shopify_token);
            $paymentSessionResolveApi->setId($businessShopifyPayment->gid);
            $paymentSessionResolveApi->setAuthorizationExpiresAt($expiredPaymentDate);
            $paymentSessionResolveApi->setUrl($url);
            $response = $paymentSessionResolveApi->handle();

            return true;
        } catch (ServerException $exception) {
            Log::channel('shopify')->error($exception);
            throw new \Exception($exception->getResponse()->getBody()->__toString());
        } catch (\Exception $exception) {
            throw new ShopifyCheckoutException($exception->getMessage());
        }
    }
}
