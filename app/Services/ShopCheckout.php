<?php
declare(strict_types=1);

namespace App\Services;


use App\Business;
use App\Business\Order;
use App\Charge;
use App\Manager\BusinessManager;
use App\Manager\BusinessManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;
use App\Enumerations\Business\PluginProvider;

/**
 * Class XeroCheckout
 * @package App\Services
 */
class ShopCheckout
{
    /**
     * @var Business
     */
    private $business;

    public function createPaymentRequest(Order $order, Business $business): array
    {
        $this->business = $business;
        try {
            $apiKey = $this->business->apiKeys()->where('is_enabled', 1)->firstOrFail()->api_key;

            $client = new Client([
                'base_uri' => 'https://' . config('app.subdomains.api') . '/v1/',
                'headers' => [
                    'X-BUSINESS-API-KEY' => $apiKey,
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'verify' => false,
            ]);

            $redirectUrl = route('shop.order.status', ['business' => $this->business->id, 'order_id' => $order->id]);

            $response = $client->post('payment-requests', [
                'form_params' => [
                    'amount' => $order->amount / 100,
                    'currency' => $order->currency,
                    'name' => $order->customer_name,
                    'email' => $order->customer_email,
                    'purpose' => 'Order #' . $order->id,
                    'channel' => PluginProvider::STORE,
                    'reference_number' => $order->id,
                    'redirect_url' => $redirectUrl,
                    'webhook' => route('shop.order.confirm', ['business' => $this->business->id, 'order_id' => $order->id]),
                ]
            ]);

            $paymentRequest = json_decode((string)$response->getBody(), true);

            return $paymentRequest;
        } catch (ServerException $exception) {
            Log::error($exception->getResponse()->getBody()->__toString());
            throw new \Exception($exception->getResponse()->getBody()->__toString());
        } catch (Exception $exception) {
            throw new \Exception($exception->getResponse()->getBody()->__toString());
        }
    }
}
