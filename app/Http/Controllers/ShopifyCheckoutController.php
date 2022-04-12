<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Shop\Controller;
use App\Services\Shopify\ShopifyCheckout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class ShopifyCheckoutController extends Controller
{
    /**
     * @throws \App\Exceptions\ShopifyCheckoutException
     * @throws \Exception
     */
    public function store(Request $request, ShopifyCheckout $shopifyCheckout)
    {
        if (!App::environment('production')) {
            Log::info(print_r($request->post(),true));
        }

        $validator = Validator::make($request->post(), [
            'id' => [
                'required',
                'string'
            ],
            'gid' => [
                'required',
                'string'
            ],
            'group' => [
                'required'
            ],
            'amount' => [
                'required',
                'numeric',
            ],
            'currency' => [
                'required',
                'string',
            ],
            'test' => [
                'required',
                'boolean',
            ],
            'merchant_locale' => [
                'required',
                'string',
            ],
            'payment_method' => [
                'required',
            ],
            'payment_method.type' => [
                'required',
            ],
            'customer' => [
                'required',
            ],
            'customer.billing_address' => [
                'required',
            ],
            'customer.billing_address.given_name' => [
                'required',
            ],
            'customer.billing_address.family_name' => [
                'required',
            ],
            'customer.email' => [
                'required',
            ],
            'customer.phone_number' => [
                'nullable',
            ],
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors(), 500);
        }

        if (Config::get('services.shopify.is_possible_test_mode')) {
            $isTestMode = $request->get('test');
        } else {
            // to testing like real payment on sandbox
            $isTestMode = false;
        }

        if (Config::get('services.shopify.business_test_sandbox_to_staging')) {
            if ($isTestMode && App::environment('sandbox')) {
                $paymentRequest = $shopifyCheckout->createPaymentRequestToSandbox($request);

                return Response::json([
                    'redirect_url' => $paymentRequest['redirect_url']
                ], 200);
            } else {
                $paymentRequest = $shopifyCheckout->createPaymentRequest($request);

                return Response::json([
                    'redirect_url' => $paymentRequest['url']
                ], 200);
            }
        } else {
            if ($isTestMode && App::environment('production')) {
                $paymentRequest = $shopifyCheckout->createPaymentRequestToSandbox($request);

                return Response::json([
                    'redirect_url' => $paymentRequest['redirect_url']
                ], 200);
            } else {
                $paymentRequest = $shopifyCheckout->createPaymentRequest($request);

                return Response::json([
                    'redirect_url' => $paymentRequest['url']
                ], 200);
            }
        }
    }
}

