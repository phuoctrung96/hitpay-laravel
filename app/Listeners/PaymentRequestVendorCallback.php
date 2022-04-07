<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use App\Business\PaymentRequest;
use App\Enumerations\Business\PaymentRequestStatus;
use App\Events\Business\SuccessCharge;
use App\Logics\Business\ChargeRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use App\Manager\ChargeManager;
use App\Http\Controllers\PaymentRequestCheckoutController;
use GuzzleHttp\Client;
use App\Enumerations\Business\PluginProvider;

/**
 * Class PaymentRequestVendorCallback
 * @package App\Listeners
 */
class PaymentRequestVendorCallback implements ShouldQueue
{
    /**
     * @param SuccessCharge $event
     * @throws \ReflectionException
     */
    public function handle(SuccessCharge $event)
    {
      try {
          $chargeManager = new ChargeManager();
          $charge = $event->charge;

          if (!$charge->is_successful_plugin_callback) {
            $plugin_data = $charge->plugin_data;

            if ($plugin_data['plugin_provider'] === PluginProvider::WOOCOMMERCE || $plugin_data['plugin_provider'] === PluginProvider::SHOPIFY) {
              $params = [
                'x_account_id' => array_key_exists('account_id', $plugin_data) ? $plugin_data['account_id'] : '',
                'x_amount' => array_key_exists('amount', $plugin_data) ? $plugin_data['amount'] : '',
                'x_currency' => array_key_exists('currency', $plugin_data) ? $plugin_data['currency'] : '',
                'x_gateway_reference' => $charge->getKey(),
                'x_reference' => array_key_exists('reference', $plugin_data) ? $plugin_data['reference'] : '',
                'x_result' => 'completed',
                'x_signature' => array_key_exists('response_signature', $plugin_data) ? $plugin_data['response_signature'] : '',
                'x_test' => array_key_exists('test', $plugin_data) ? $plugin_data['test'] : '',
                'x_timestamp' => $plugin_data['timestamp']
              ];

              if ($plugin_data['plugin_provider'] === PluginProvider::WOOCOMMERCE) {
                $params['x_order_id'] = $plugin_data['order_id'];
              }

              $client = new Client();
              $client->request('POST', $plugin_data['url_callback'], [
                  'form_params' => $params,
                  'headers' => [
                      'User-Agent' => 'HitPay v1.0',
                  ]
              ]);
            } else {
              PaymentRequestCheckoutController::performVendorCallback($charge, $chargeManager, $charge->paymentRequest);
            }
          }
      } catch (\Exception $e) {
        // suppress log errors
        Log::info('webhook failed ' . $e->getMessage());
      }
    }
}
