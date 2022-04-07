<?php

namespace App\Http\Controllers;

use App\Business;
use App\Business\Charge;
use App\Business\PaymentProvider as PaymentProviderModel;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\PaymentProvider;
use App\Helpers\Currency;
use App\Http\Controllers\Shop\Controller;
use App\Manager\BusinessManagerInterface;
use App\Manager\ChargeManagerInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;

class PaymentGatewayCheckoutController extends Controller
{
    const ENABLED_CURRENCIES_FOR_PAYNOW = ['sgd'];

    /**
     * @param Request $request
     * @param ChargeManagerInterface $chargeManager
     *
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function paymentCheckout(
        Request $request,
        ChargeManagerInterface $chargeManager,
        BusinessManagerInterface $businessManager,
        string $provider
    ) {
        $business   = Business::findOrFail($request->get('business_id'));
        $data       = mapPluginFields($provider, $request->post());

        $referer    = isset($data['checkout_url']) ? $data['checkout_url'] : $request->headers->get('referer');
        $countries  = [];

        if (!Gate::allows('view-checkout', $business)) {
            return Response::view('shop.checkout.error', compact('business', 'data', 'referer'));
        }

        $charge             = $chargeManager->getFindByPluginProviderAndProviderReference($provider, $data['reference']);
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

        $paymentMethods             = $businessManager->getBusinessPaymentMethods($business, $provider);
        $paymentMethods = $this->checkPayNow($charge->currency, $paymentMethods);
        $amount = Currency::getReadableAmount($charge->amount, $charge->currency);
        $data['response_signature'] = $chargeManager->generateSignature($charge, $request);

        $symbol = Currency::getCurrencySymbol($charge->currency);
        $zeroDecimal = Currency::isZeroDecimal($charge->currency);

        $chargeManager->updateDataSignature($charge, $data['response_signature']);

        $defaultUrlCompleted = URL::route('securecheckout.payment.request.completed', [
            'p_charge' => $charge->getKey(),
        ]);

        $stripePublishableKey = $businessManager->getStripePublishableKey($business);

        return Response::view('shop.checkout', compact(
            'charge', 'business',
            'countries', 'paymentMethods', 'amount',
            'data', 'referer', 'defaultUrlCompleted',
            'symbol', 'zeroDecimal', 'stripePublishableKey'
        ));
    }

    /**
     * @param string $currency
     * @param array $paymentMethods
     * @return array
     */
    protected function checkPayNow($currency, $paymentMethods)
    {
        if (!in_array($currency, static::ENABLED_CURRENCIES_FOR_PAYNOW)) {
            $key = array_search(PaymentMethodType::PAYNOW, $paymentMethods);
            if ($key !== false) {
                unset($paymentMethods[$key]);
            }
        }

        $paymentMethods = array_values($paymentMethods);

        return $paymentMethods;
    }
}
