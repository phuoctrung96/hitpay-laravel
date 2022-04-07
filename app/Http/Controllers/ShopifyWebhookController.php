<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Shop\Controller;
use App\Services\Shopify\ShopifyCheckout;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class ShopifyWebhookController extends Controller
{
    /**
     * @param Request $request
     * @param ShopifyCheckout $shopifyCheckout
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\ShopifyCheckoutException
     * @throws \Exception
     */
    public function store(Request $request, ShopifyCheckout $shopifyCheckout)
    {
        $validator = Validator::make($request->post(), [
            'payment_id' => [
                'required',
                'string'
            ],
            'payment_request_id' => [
                'required',
                'string'
            ],
            'phone' => [
                'nullable'
            ],
            'amount' => [
                'required',
                'string',
            ],
            'currency' => [
                'required',
                'string',
            ],
            'status' => [
                'required',
            ],
            'reference_number' => [
                'required',
                'string',
            ],
            'hmac' => [
                'required',
            ]
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors(), 500);
        }

        $responseWebhook = $shopifyCheckout->listenWebhook($request);

        return response()->json(['status' => $responseWebhook], 200);
    }
}
