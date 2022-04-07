<?php
declare(strict_types=1);

namespace App\Services;


use App\Business;
use App\Exceptions\XeroCheckoutException;
use App\Http\Requests\XeroInvoiceRequest;
use App\Manager\BusinessManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

/**
 * Class XeroCheckout
 * @package App\Services
 */
class XeroCheckout
{
    /**
     * @var BusinessManagerInterface
     */
    private $businessManager;

    public function __construct()
    {
        $this->businessManager = resolve(BusinessManagerInterface::class);
    }

    /**
     * @var Business
     */
    private $business;

    public function createPaymentRequest(XeroInvoiceRequest $request): array
    {
        try {
            $apiKey = $this->getOrganizationApiKey($request->input('shortCode'));

            $client = new Client([
                'base_uri' => 'https://' . config('app.subdomains.api').'/v1/',
                'headers' => [
                'X-BUSINESS-API-KEY' => $apiKey,
                'X-Requested-With' => 'XMLHttpRequest',
                'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'verify' => false,
            ]);

            $redirectUrl = $request->server('HTTP_REFERER');
            if(empty($redirectUrl)) {
                $redirectUrl = 'https://go.xero.com/AccountsReceivable/View.aspx?InvoiceID=' . $request->input('invoice');
            }


            $response = $client->post('payment-requests', [
                'form_params' => [
                    'amount' => $request->input('amount'),
                    'currency' => $request->input('currency'),
                    'email' => $this->getXeroInvoiceEmail($request->input('invoice')),
                    'reference_number' => $request->input('invoice'),
                    'redirect_url' => $redirectUrl,
                    'send_email' => 'true',
                    'webhook' => route('xero.checkout.confirm', ['invoice' => $request->input('invoice'), 'shortCode' => $request->input('shortCode')]),
                    'channel' => 'api_xero',
                ]
            ]);

            $paymentRequest = json_decode((string) $response->getBody(), true);

            return $paymentRequest;
	} catch (ServerException $exception) {
            Log::channel('xero')->error($exception);
            throw new \Exception($exception->getResponse()->getBody()->__toString());
        } catch (Exception $exception) {
            throw new XeroCheckoutException($exception->getMessage());
        }
    }

    public function getPaymentRequestStatus(Business\PaymentRequest $paymentRequest)
    {
        try {
            $apiKey = $paymentRequest->business->apiKeys()->where('is_enabled', 1)->firstOrFail()->api_key;

            $client = new Client([
                'base_uri' => 'https://' . config('app.subdomains.api').'/v1/',
                'headers' => [
                    'X-BUSINESS-API-KEY' => $apiKey,
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'verify' => false,
            ]);

            $response = $client->get('payment-requests/' . $paymentRequest->id);

            $paymentRequest = json_decode((string) $response->getBody(), true);

            return $paymentRequest;
        } catch (ServerException $exception) {
            Log::channel('xero')->error($exception);
            throw new \Exception($exception->getResponse()->getBody()->__toString());
        } catch (Exception $exception) {
            throw new XeroCheckoutException($exception->getMessage());
        }
    }

    private function getOrganizationApiKey(string $shortCode): string
    {
        $business = $this->getBusiness($shortCode);

        $apiKey = $business->apiKeys()->where('is_enabled', 1)->firstOrFail();

        return $apiKey->api_key;
    }

    /**
     * @param $shortCode
     * @return Business
     */
    private function getBusiness($shortCode): Business
    {
        if(is_null($this->business)) {
            $this->business = Business::whereHas('xeroOrganizations', function($query) use($shortCode) {
                return $query->where('short_code', $shortCode);
            })->firstOrFail();
        }

        return $this->business;
    }

    private function getAvailablePaymentMethods(string $shortCode, string $currency): array
    {
        $business = $this->getBusiness($shortCode);

        $paymentMethods = $this->businessManager->getByBusinessAvailablePaymentMethods($business, $currency);

        return array_keys($paymentMethods);
    }

    private function getXeroInvoiceEmail(string $invoiceId)
    {
        try {
            $accountingApi = XeroApiFactory::makeAccountingApi($this->business);
            /** @var \XeroAPI\XeroPHP\Models\Accounting\Invoices $invoice */
            if($invoices = $accountingApi->getInvoice($this->business->xero_tenant_id, $invoiceId)) {
                $invoice = $invoices->getInvoices()[0];
                return $invoice->getContact()->getEmailAddress();
            }
            return $this->business->email;
        }
	    catch (exception $e) {
		// do nothing
        }
        return '';
    }
}
