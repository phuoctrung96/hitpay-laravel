<?php

namespace App\Services;

use App\Business\GatewayProvider;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use XeroAPI\XeroPHP\Api\AccountingApi;
use XeroAPI\XeroPHP\Models\Accounting\PaymentService;

class XeroPaymentService implements ShouldQueue
{
    /**
     * @param GatewayProvider $gatewayProvider
     * @return false
     * @throws Exception
     */
    public function createPaymentService(GatewayProvider $gatewayProvider)
    {
        $business = $gatewayProvider->business;

        if (!$this->shouldCreateXeroPaymentService($gatewayProvider)) {
            return false;
        }

        try {
            $accountingApi = XeroApiFactory::makeAccountingApi($business);

            /** @var \XeroAPI\XeroPHP\Models\Accounting\PaymentServices $result */
            $result = $accountingApi->createPaymentService($business->xero_tenant_id, [
                'paymentServices' => [
                    [
                        'paymentServiceName' => "PayNow",
                        'paymentServiceUrl' => "https://".config('app.subdomains.securecheckout')."/xero/?invoice=[INVOICENUMBER]&currency=[CURRENCY]&amount=[AMOUNTDUE]&shortCode=[SHORTCODE]",
                        'payNowText' => $business->paynow_btn_text ?? "Pay with HitPay",
                    ]
                ]
            ]);

            /** @var PaymentService $paymentService */
            $paymentService = $result->getPaymentServices()[0];
            $gatewayProvider->xero_id = $paymentService->getPaymentServiceId();
            $gatewayProvider->xero_branding_theme = $business->xero_branding_theme;
            $gatewayProvider->save();

            $accountingApi->createBrandingThemePaymentServices($business->xero_tenant_id, $gatewayProvider->xero_branding_theme, $paymentService);
        } catch (\XeroAPI\XeroPHP\ApiException $e) {
            $response = json_decode($e->getResponseBody());
            Log::channel('xero')->error($e);
            throw new Exception('Error : Xero ' . $response->Message);
        }
    }

    private function shouldCreateXeroPaymentService(GatewayProvider $gatewayProvider): bool
    {
        return $gatewayProvider->name == 'xero' && empty($gatewayProvider->xero_id);
    }
}
