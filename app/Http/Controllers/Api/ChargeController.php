<?php

namespace App\Http\Controllers\Api;

use App\Business;
use App\Manager\ChargeManagerInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateShopifyChargeRequest;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;

class ChargeController extends Controller
{
    /**
     * ChargeController constructor.
     */
    public function __construct()
    {
        //$this->middleware('auth:plugin');
    }

    /**
     * @param CreateChargeRequest $request
     * @param Business $business
     * @param ChargeManagerInterface $chargeManager
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function create(CreateShopifyChargeRequest $request, Business $business, ChargeManagerInterface $chargeManager) 
    {
    }

    /**
     * @param Request $request
     */
    public function void(Request $request) 
    {
        return [];
    }

    /**
     * @param Request $request
     */
    public function refund(Request $request) 
    {
        return [];
    }
}