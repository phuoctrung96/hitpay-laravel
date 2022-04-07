<?php

namespace App\Http\Controllers;

use App\Business;
use App\Business\Charge;
use App\Business\PaymentProvider as PaymentProviderModel;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\PaymentProvider;
use App\Helpers\Currency;
use App\Http\Requests\CreateShopifyChargeRequest;
use App\Http\Controllers\Shop\Controller;
use App\Manager\ChargeManagerInterface;
use App\Manager\BusinessManagerInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzleHttpRequest;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\URL;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use App\Enumerations\Business\ChargeStatus;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    /**
     * @param CreateShopifyChargeRequest $request
     * @param ChargeManagerInterface $chargeManager
     *
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function shopify(CreateShopifyChargeRequest $request, ChargeManagerInterface $chargeManager, BusinessManagerInterface $businessManager)
    {
        $business   = Business::findOrFail($request->get('business_id'));
        $data       = mapPluginFields(PluginProvider::SHOPIFY, $request->post());
        $referer    = $request->headers->get('referer');
        $countries  = [];

        if (!Gate::allows('view-checkout', $business)) {
            return Response::view('shop.checkout.error', compact('business', 'data', 'referer'));
        }

        $charge             = $chargeManager->getFindByPluginProviderAndProviderReference(PluginProvider::SHOPIFY, $data['reference']);
        $data['timestamp']  = $request->get('x_timestamp');

        if (!$charge instanceof Charge) {
            $charge = $chargeManager->createRequiresPaymentMethod($business, $data);
        }

        $paymentProvider = $business
                ->paymentProviders
                ->where('payment_provider', PaymentProvider::STRIPE_SINGAPORE)
                ->first()
            ;

        if ($paymentProvider instanceof PaymentProviderModel) {
            $countries = $this->getCheckoutOptions($business, Config::get('app.subdomains.securecheckout'))['countries_list'];
        }

        $paymentMethods             = $businessManager->getBusinessPaymentMethods($business, PluginProvider::SHOPIFY);
        $amount = Currency::getReadableAmount($charge->amount, $charge->currency);
        $data['response_signature'] = $chargeManager->generateShopifySignature($charge, $request);
        $symbol = Currency::getCurrencySymbol($charge->currency);
        $chargeManager->updateDataSignature($charge, $data['response_signature']);
        $defaultUrlCompleted    = URL::route('securecheckout.payment.request.completed', ['p_charge' => $charge->getKey()]);

        [
            $isEligible,
            $campaignCashbackAmount,
            $campaign,
            $campaignRule
        ] = $charge->isEligibleForCampaignCashback(true);

        $cashback = $business->getRegularCashback($charge, true);

        $zeroDecimal = Currency::isZeroDecimal($charge->currency);

        $stripePublishableKey = $businessManager->getStripePublishableKey($business);

        return Response::view('shop.checkout', compact(
            'charge', 'business', 'countries', 'paymentMethods', 'amount', 'data', 'referer',
            'defaultUrlCompleted', 'symbol', 'cashback', 'campaignRule', 'zeroDecimal', 'stripePublishableKey'));
    }

    public function shopifyCompleted(Request $request, ChargeManagerInterface $chargeManager, BusinessManagerInterface $businessManager) {
      if ($request->query->has('statusCode') && $request->query->has('transactionID')) {
        $charge = Charge::find($request->query->get('transactionID'));

        if ($charge instanceof Charge) {
          if ($charge->status === ChargeStatus::SUCCEEDED && $request->query->get('statusCode') === '1000') {
            $pluginData = $charge->plugin_data;

            // Callback
            try {
              $params = array(
                'x_account_id' => $pluginData['account_id'],
                'x_amount' => $pluginData['amount'],
                'x_currency' => $pluginData['currency'],
                'x_gateway_reference' => $charge->id,
                'x_reference' => $pluginData['reference'],
                'x_result' => 'completed',
                'x_signature' => $pluginData['response_signature'],
                'x_test' => $pluginData['test'],
                'x_timestamp' => $pluginData['timestamp']
              );

              if ($pluginData['plugin_provider'] == 'woocommerce') {
                $params['x_order_id'] = $pluginData['order_id'];
              }

              $client = new Client();
              $res = $client->request('POST', $pluginData['url_callback'], [
                'form_params' => $params,
                'headers' => [
                  'User-Agent' => 'HitPay v1.0',
                ]
              ]);

              if ($res->getStatusCode() === 200) {
                // Redirect back to merchant
                return redirect()->away($pluginData['url_complete']);
              }
            }  catch (\Exception $e) {
              // just silence exception
              Log::error('Failed to call merchant webhook'.$e->getMessage());
            }

            // If we get there - something went wrong
            if (array_key_exists('url_cancel', $pluginData)) {
              return redirect()->away($pluginData['url_cancel']);
            } else {
              // ???
              Log::error('No url_cancel');
            }
          }
        } else {
          // Charge id is not valid
          Log::error('Charge id is not valid');
        }

      } else {
        // !!! no required parameters
      }

      // There is an error if we get there
      $referer = $request->headers->get('referer');
      $message = 'Your payment was not successful. Please try again.';
      return Response::view('shop.checkout.simpleerror', compact('referer', 'message'));
    }

    public function shopifyCallback(Request $request)
    {
        try {
            $client     = new Client();
            $params     = $request->except(['url_callback']);

            $res = $client->request('POST', $request->get('url_callback'), [
                'form_params' => $params,
                'headers' => [
                    'User-Agent' => 'HitPay v1.0',
                ]
            ]);

            if ($res->getStatusCode() === 200) {
                return Response::json(['success' => true]);
            }

            /*
            $query = \GuzzleHttp\Psr7\build_query($params, PHP_QUERY_RFC1738);
            $request    = new GuzzleHttpRequest(
                'POST',
                $request->get('url_callback'),
                ['Content-Type' => 'application/x-www-form-urlencoded'],
                $query
            );

            $promise    = $client->sendAsync($request);

            $promise->then(
                function (ResponseInterface $res) {
                    return Response::json(['success' => true]);
                },
                function (RequestException $e) {
                    return Response::json(['success' => false, 'result' => $e->getMessage()]);
                }
            );*/
        } catch (ClientException $e) {
            $response   = $e->getResponse();
            $result     =  json_decode($response->getBody()->getContents());
        }  catch (\Exception $e) {
            $result     =  $e->getMessage();
        }

        return Response::json(['success' => false, 'result' => $result]);
    }
}



