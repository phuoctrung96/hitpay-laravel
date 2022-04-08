<?php

namespace App\Http\Controllers;

use App\Business;
use App\Business\Charge;
use App\Business\PaymentRequest;
use App\Business\PaymentProvider as PaymentProviderModel;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\CurrencyCode;
use App\Enumerations\PaymentProvider;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\PaymentRequestStatus;
use App\Helpers\Currency;
use App\Helpers\Link;
use App\Http\Controllers\Shop\Controller;
use App\Manager\ChargeManagerInterface;
use App\Manager\BusinessManagerInterface;
use App\Manager\PaymentRequestManagerInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzleHttpRequest;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Validator;

class PaymentRequestCheckoutController extends Controller
{
    /**
     * @param Request $request
     * @param ChargeManagerInterface $chargeManager
     *
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function paymentCheckout(
        Request $request,
        Business $business,
        PaymentRequest $paymentRequest,
        ChargeManagerInterface $chargeManager,
        BusinessManagerInterface $businessManager,
        PaymentRequestManagerInterface $paymentRequestManager
    )
    {
        $referer = $request->headers->get('referer');

        if ($request->query->has('statusCode') && $request->query->has('transactionID')) {
            $charge = Charge::find($request->query->get('transactionID'));
            $message = 'Your payment was not successful. Please try again.';

            if ($charge instanceof Charge && $request->query->get('statusCode') === '1000') {
                if ($charge->status === ChargeStatus::SUCCEEDED) {
                    $chargePaymentRequest = PaymentRequest::findOrFail($charge->plugin_provider_reference);
                    Log::info('Deeplink success for ' . $charge->getKey());

                    if (isset($chargePaymentRequest->redirect_url)) {
                        $url = $chargePaymentRequest->redirect_url . '?status=completed&reference=' . $chargePaymentRequest->id;
                        return redirect()->away($url);
                    } else {
                        return redirect()->route('securecheckout.payment.request.completed', ['p_charge' => $charge->getKey()]);
                    }
                } else {
                  if ($request->query->has('noWait')) {
                    return Response::view('shop.checkout.simpleerror', [
                      'referer' => $request->query->get('backURL'),
                      'message' => $message
                    ]);
                  } else {
                    // We can get this redirect too fast, so wait order completion
                    return Response::view('shop.checkout.processing', [
                      'business' => $business,
                      'charge_id' => $charge->id,
                      'backURL' => $referer
                    ]);
                  }
                }
            } else {
                Log::error('Invalid charge ID or change is not succeeded');
            }

            // There is an error if we get there
            return Response::view('shop.checkout.simpleerror', compact('referer', 'message'));
        } else {
            $charge = $chargeManager->getFindByProviderReference($paymentRequest->getKey(), 'succeeded');

            if ($charge instanceof Charge
                && $charge->status === ChargeStatus::SUCCEEDED
                && !$paymentRequest->allow_repeated_payments
                && $paymentRequest->status === PaymentRequestStatus::COMPLETED
            ) {
                return redirect()->route('securecheckout.payment.request.completed', ['p_charge' => $charge->getKey()]);
            }
        }

        if ($paymentRequest->is_expired) {
            return redirect()->route('securecheckout.payment.request.expired', [
                'business_slug' => '@' . $business->slug,
                'payment_request_id' => $paymentRequest->getKey()
            ]);
        }

        if (!Gate::allows('view-checkout', $business)) {
          return Response::view('shop.checkout.error', compact('business', 'referer'));
        }

        return Response::view(
            'shop.checkout.request',
            $this->getPaymentRequestParams(
                $request,
                $business,
                $paymentRequest,
                $chargeManager,
                $businessManager,
                $paymentRequestManager,
                $charge
            )
        );
    }

    public function businessCheckoutDropin(
        Request $request,
        PaymentRequest $paymentRequest,
        ChargeManagerInterface $chargeManager,
        BusinessManagerInterface $businessManager,
        PaymentRequestManagerInterface $paymentRequestManager
    )
    {
        $charge = $chargeManager->getFindByProviderReference($paymentRequest->getKey(), 'succeeded');
        $business = Business::find($paymentRequest->business_id);

        if ($charge instanceof Charge
            && $charge->status === ChargeStatus::SUCCEEDED
            && !$paymentRequest->allow_repeated_payments
            && $paymentRequest->status === PaymentRequestStatus::COMPLETED
        ) {
            $params['business'] = $business->getFilteredData();
            $params['alreadyPaid'] = true;
        } else {
            $params = $this->getPaymentRequestParams(
                $request,
                $business,
                $paymentRequest,
                $chargeManager,
                $businessManager,
                $paymentRequestManager,
                $charge
            );

            $params['alreadyPaid'] = false;

            $this->addDropInParams($params);
        }

        return Response::json($params)->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    public function businessCheckoutDropinDefault(
        Request $request,
        Business $business,
        ChargeManagerInterface $chargeManager,
        BusinessManagerInterface $businessManager,
        PaymentRequestManagerInterface $paymentRequestManager
    )
    {
        $paymentRequest = PaymentRequest::where('business_id', $business->getKey())->where('is_default', true)->first();

        if (!$paymentRequest instanceof PaymentRequest) {
            // !!!
            abort(404);
        }

        $charge = $chargeManager->getFindByPluginProviderAndProviderReference(PluginProvider::CUSTOM, $paymentRequest->getKey());

        if ($charge instanceof Charge
            && $charge->status === ChargeStatus::SUCCEEDED
            && !$paymentRequest->allow_repeated_payments
            && $paymentRequest->status === PaymentRequestStatus::COMPLETED) {
            $params['alreadyPaid'] = true;
        } else {
            $params = $this->getPaymentRequestParams(
                $request,
                $business,
                $paymentRequest,
                $chargeManager,
                $businessManager,
                $paymentRequestManager,
                null,
                true // default mode
            );

            $params['alreadyPaid'] = false;

            $this->addDropInParams($params);
        }

        return Response::json($params)->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * @param Request $request
     * @param ChargeManagerInterface $chargeManager
     *
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function businessPaymentCheckout(
        Request $request,
        Business $business,
        ChargeManagerInterface $chargeManager,
        BusinessManagerInterface $businessManager
    )
    {
        $input_amt = $request->input('amt');
        $paymentRequest = PaymentRequest::where('business_id', $business->getKey())->where('is_default', true)->first();

        if (!$paymentRequest instanceof PaymentRequest) {
            abort(404);
        }

        $charge = $chargeManager->getFindByPluginProviderAndProviderReference(PluginProvider::CUSTOM, $paymentRequest->getKey());

        if ($charge instanceof Charge
            && $charge->status === ChargeStatus::SUCCEEDED
            && !$paymentRequest->allow_repeated_payments
            && $paymentRequest->status === PaymentRequestStatus::COMPLETED
        ) {
            return redirect()->route('securecheckout.payment.request.completed', ['p_charge' => $charge->getKey()]);
        }

        if ($paymentRequest->is_expired) {
            return redirect()->route('securecheckout.payment.request.expired', [
                'business_slug' => '@' . $business->slug,
                'payment_request_id' => $paymentRequest->getKey()
            ]);
        }

        $data = [
            'plugin_provider' => PluginProvider::CUSTOM,
            'reference' => $paymentRequest->getKey(),
            'currency' => $paymentRequest->currency,
            'description' => $request->get('reference_number', $paymentRequest->purpose),
            'amount' => $paymentRequest->amount,
            'url_callback' => $paymentRequest->webhook,
            'url_complete' => $paymentRequest->redirect_url,
            'send_sms' => $paymentRequest->send_sms,
            'send_email' => $paymentRequest->send_email,
            'customer_email' => $request->get('email', $paymentRequest->email),
            'customer_phone' => $paymentRequest->phone,
            'customer_name' => $paymentRequest->name,
            'order_id' => $request->get('reference_number'),
        ];

        $referer = $request->headers->get('referer');
        $countries = [];

        if (!Gate::allows('view-checkout', $business)) {
            return Response::view('shop.checkout.error', compact('business', 'data', 'referer'));
        }

        $data['timestamp'] = gmdate("Y-m-d\TH:i:s\Z");

        $charge = $chargeManager->createRequiresPaymentMethod($business, $data);

        $paymentProvider = $business
            ->paymentProviders
            ->where('payment_provider', PaymentProvider::STRIPE_SINGAPORE)
            ->first();

        if ($paymentProvider instanceof PaymentProviderModel) {
            $countries = $this->getCheckoutOptions($business, Config::get('app.subdomains.securecheckout'))['countries_list'];
        }
        $defaultLink = PluginProvider::getProviderByChanel(PluginProvider::LINK);
        $paymentMethods = $businessManager->getBusinessPaymentMethods($business, $defaultLink);

        if (!preg_match("/^[0-9]{1,}$/", $input_amt)) {
            $format_amt = $charge->amount;
        } else {
            $format_amt = $input_amt;
        }

        $amount = number_format(getReadableAmountByCurrency($charge->currency, $format_amt), 2);
        $symbol = Currency::getCurrencySymbol($charge->currency);
        $mode = 'default';

        [
            $isEligible,
            $campaignCashbackAmount,
            $campaign,
            $campaignRule
        ] = $charge->isEligibleForCampaignCashback(true);

        $cashback = $business->getRegularCashback($charge, true);

        $defaultMethod = $this->getMethodParam($request);
        $defaultUrlCompleted = URL::route('securecheckout.payment.request.completed', ['p_charge' => $charge->getKey()]);

        $zeroDecimal = Currency::isZeroDecimal($charge->currency);

        $stripePublishableKey = $businessManager->getStripePublishableKey($business);

        return Response::view('shop.checkout.request', compact(
            'charge', 'business', 'countries',
            'paymentMethods', 'amount', 'data',
            'referer', 'defaultUrlCompleted', 'symbol',
            'mode', 'defaultMethod', 'cashback',
            'campaignRule', 'zeroDecimal',
            'stripePublishableKey'
        ));
    }

    /**
     * @param Charge $charge
     *
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function paymentCheckoutCompleted(Charge $charge)
    {
        $business = $charge->business;

        return Response::view('shop.checkout.completed', compact('charge', 'business'));
    }

    /**
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function paymentCheckoutExpired(Business $business)
    {
        return Response::view('shop.checkout.expired', compact('business'));
    }

    public function test()
    {
        try {
            $client = new Client();

            $params = [
                'gateway_reference' => 2222,
                'amount' => 1,
                'currency' => 'usd',
                'payment_request_reference' => 111,
                'status' => 'completed'
            ];

            $response = $client->request('POST', 'https://webhook.site/#!/51268e0a-0783-44c1-9859-8dd348b24814/814378d9-99b8-45fa-93a5-008ebc6f3b90/1', [
                'form_params' => $params
            ]);

            if ($response->getStatusCode() === 200) {
                return Response::json(['success' => true]);
            }

            $result = $response->getStatusCode();
        } catch (ClientException $e) {
        } catch (\Exception $e) {
        }
    }

    public static function performVendorCallback($charge, $chargeManager, $paymentRequest)
    {
        $data = $charge->plugin_data;
        $apiKey = $charge->business->apiKeys()->first();

        $signature = $chargeManager->generateSignatureArray($apiKey->salt, [
            'payment_id' => $charge->getKey(),
            'payment_request_id' => $paymentRequest->getkey(),
            'phone' => '',
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'status' => 'completed',
            'reference_number' => $paymentRequest->reference_number
        ]);

        $params = [
            'payment_id' => $charge->getKey(),
            'payment_request_id' => $paymentRequest->getkey(),
            'phone' => '',
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'status' => 'completed',
            'reference_number' => $paymentRequest->reference_number,
            'hmac' => $signature
        ];

        $client = new Client();

        if ($paymentRequest->channel === PluginProvider::GOOGLE_FORMS){
            parse_str(parse_url($data['url_callback'])['query'], $params);
           if (isset($params['code'])){
                $headers['Authorization'] = 'Bearer ' . $params['code'];
                 $data['url_callback'] = $data['url_callback'].'&payment_request_id='.$paymentRequest->getkey();
           }
        }

        $headers['User-Agent'] = 'HitPay v1.0';

        $response = $client->request('POST', $data['url_callback'], [
            'form_params' => $params,
            'headers' => $headers
        ]);

        if ($response->getStatusCode() === 200) {
            $chargeManager->markAsSuccessfulPluginCallback($charge);
            return true;
        } else {
            $chargeManager->incrementRetryCount($charge);
            $chargeManager->markAsFailedPluginCallback($charge);
            return false;
        }
    }

    function getMethodParam($request)
    {
        $method = $request->query->get('method');

        if ($method) {
            $validator = Validator::make(
                ['method' => $method],
                ['method' => 'regex:/^[a-zA-Z\_]+$/|max:20']
            );

            if ($validator->fails()) {
                $method = '';
            }
        }

        return $method;
    }

    function addDropInParams(&$params)
    {
        $business = $params['business'];

        $params['customisation'] = $business->checkoutCustomisation();
        // replace business with filtered version
        $params['business'] = $business->getFilteredData();
        // stripe
        $params['stripePkey'] = config('services.stripe.sg.key');
        // pusher
        $params['pusher'] = [
            'key' => config('broadcasting.connections.pusher.key'),
            'cluster' => config('broadcasting.connections.pusher.options.cluster')
        ];
        // umami
        $params['umami'] = [
            'umamiUrl' => config('checkout.umamiUrl'),
            'umamiAppId' => config('checkout.umamiAppId')
        ];

        $params['timeout'] = config('app.payment_gateway.status_check_timeout');
    }

    function getPaymentRequestParams(
        $request,
        $business,
        $paymentRequest,
        $chargeManager,
        $businessManager,
        $paymentRequestManager,
        $charge,
        $default = false
    )
    {
        $data = [
            'plugin_provider' => PluginProvider::CUSTOM,
            'reference' => $paymentRequest->getKey(),
            'currency' => $paymentRequest->currency,
            'description' => $paymentRequest->purpose,
            'amount' => $paymentRequest->amount,
            'url_callback' => $paymentRequest->webhook,
            'url_complete' => $paymentRequest->redirect_url,
            'send_sms' => $paymentRequest->send_sms,
            'send_email' => $paymentRequest->send_email,
            'customer_email' => $paymentRequest->email,
            'customer_phone' => $paymentRequest->phone,
            'customer_name' => $paymentRequest->name,
            'order_id' => $paymentRequest->reference_number,
            'commission_rate' => $paymentRequest->commission_rate,
            'platform_business_id' => $paymentRequest->platform_business_id,
            'channel' => $paymentRequest->channel,
        ];

        $provider = PluginProvider::getProviderByChanel($data['channel']);

        // At this point referer === $request->headers->get('referer')
        if ($paymentRequest->redirect_url) {
            $referer = Link::getCanceledLink($paymentRequest->redirect_url, $paymentRequest->getKey());
        } else {
            $referer = '';
        }

        $countries = [];

        $data['timestamp'] = gmdate("Y-m-d\TH:i:s\Z");

        if (!$charge instanceof Charge || $paymentRequest->allow_repeated_payments) {
            $charge = $chargeManager->createRequiresPaymentMethod($business, $data);
        }

        $paymentProvider = $business
            ->paymentProviders
            ->where('payment_provider', PaymentProvider::STRIPE_SINGAPORE)
            ->first();

        if ($paymentProvider instanceof PaymentProviderModel) {
            $countries = $this->getCheckoutOptions($business, Config::get('app.subdomains.securecheckout'))['countries_list'];
        }

        $paymentMethods = [];

        if ($default) {
            $defaultLink = PluginProvider::getProviderByChanel(PluginProvider::LINK);
            $paymentMethods = $businessManager->getBusinessPaymentMethods($business, $defaultLink);
        } else {
            // payment request mode
            if (!$paymentMethods = $businessManager->getBusinessPaymentRequestMethods($business, $paymentRequest->payment_methods)) {
                $paymentMethods = $businessManager->getBusinessProviderPaymentMethods($business, $provider, $paymentRequest->currency);
            }
        }

        [
            $isEligible,
            $campaignCashbackAmount,
            $campaign,
            $campaignRule
        ] = $charge->isEligibleForCampaignCashback(true);

        $cashback = $business->getRegularCashback($charge, true);

        $amount = Currency::getReadableAmount($charge->amount, $charge->currency);
        $symbol = Currency::getCurrencySymbol($charge->currency);
        $mode = 'payment-request';
        $zeroDecimal = Currency::isZeroDecimal($charge->currency);

        $defaultMethod = $this->getMethodParam($request);
        $defaultUrlCompleted = URL::route('securecheckout.payment.request.completed', ['p_charge' => $charge->getKey()]);

        $stripePublishableKey = $businessManager->getStripePublishableKey($business);

        return compact(
            'business', 'charge', 'countries',
            'paymentMethods', 'amount', 'data', 'referer',
            'defaultUrlCompleted', 'symbol', 'mode',
            'defaultMethod', 'cashback',
            'campaignRule', 'zeroDecimal', 'stripePublishableKey'
        );
    }
}
