<?php

namespace App\Http\Controllers;

use App\BusinessShopifyPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopifyCheckoutRedirectController extends Controller
{
    /**
     * @throws \Exception
     */
    public function __invoke(Request $request)
    {
        sleep(10);

        $invoiceId = $request->get('invoice');

        if ($invoiceId === null) {
            throw new \Exception("Invoice not set on shopify checkout redirection");
        }

        $businessId = $request->get('business_id');

        if ($businessId === null) {
            throw new \Exception("Business ID not set on shopify checkout redirection");
        }

        $businessShopifyPayment = BusinessShopifyPayment::where('invoice_id', $invoiceId)
            ->where('business_id', $businessId)->first();

        if (!$businessShopifyPayment instanceof BusinessShopifyPayment) {
            throw new \Exception("Invoice ID {$invoiceId} not found with business ID {$businessId}
                from shopify checkout redirection");
        }

        if (!is_array($businessShopifyPayment->data)) {
            throw new \Exception("Shopify Payment ID {$businessShopifyPayment->id}, Invoice ID {$invoiceId},
                business ID {$businessId} from shopify checkout redirection is not have array data. Do repeat redirect!");
        }

        $paymentResolveData = $businessShopifyPayment->data;

        if (!$this->validatePaymentSessionResolveData($paymentResolveData['data'])) {
            throw new \Exception("There is issue on payment resolve data with
                Shopify Payment ID {$businessShopifyPayment->id}, Invoice ID {$invoiceId}, business ID {$businessId}");
        }

        $redirectUrl = $paymentResolveData['data']['paymentSessionResolve']['paymentSession']['nextAction']['context']['redirectUrl'];

        header("Location: {$redirectUrl}");

        exit;
    }

    /**
     * @param array $paymentResolveData
     * @return bool
     */
    private function validatePaymentSessionResolveData(array $paymentResolveData): bool
    {
        $status = true;
        $message = null;

        if (!isset($paymentResolveData['paymentSessionResolve'])) {
            $message = "No have `paymentSessionResolve` key";
            $status = false;
        }

        if (!isset($paymentResolveData['paymentSessionResolve']['paymentSession'])) {
            $message = "No have `paymentSession` key";
            $status = false;
        }

        if (!isset($paymentResolveData['paymentSessionResolve']['paymentSession']['nextAction'])) {
            $message = "No have `nextAction` key";
            $status = false;
        }

        if (!isset($paymentResolveData['paymentSessionResolve']['paymentSession']['nextAction']['context'])) {
            $message = "No have `context` key";
            $status = false;
        }

        if (!isset($paymentResolveData['paymentSessionResolve']['paymentSession']['nextAction']['context']['redirectUrl'])) {
            $message = "No have `redirectUrl` key";
            $status = false;
        }

        if (!$status) {
            Log::critical("PaymentSessionResolve data issues with message: {$message}");
        }

        return $status;
    }
}
