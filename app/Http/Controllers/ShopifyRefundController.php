<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Shop\Controller;
use App\Services\Shopify\ShopifyRefund;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class ShopifyRefundController extends Controller
{
    /**
     * @param Request $request
     * @param ShopifyRefund $shopifyRefund
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(Request $request, ShopifyRefund $shopifyRefund)
    {
        if (App::environment('local') || App::environment('staging')) {
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
            'payment_id' => [
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
            'merchant_locale' => [
                'required',
                'string',
            ],
            'proposed_at' => [
                'required',
            ],
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors(), 500);
        }

        $shopifyRefund->createFromRequest($request);

        return Response::json([], 201);
    }
}


