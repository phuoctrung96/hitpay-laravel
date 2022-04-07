<?php

namespace App\Http\Controllers\Api\Plugin;

use App\Business;
use App\Manager\BusinessManagerInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Response;

class BusinessController extends Controller
{
    /**
     * BusinessController constructor.
     */
    public function __construct()
    {
        //$this->middleware('auth:plugin');
    }

    /**
     * @param Business $business
     * @param BusinessManagerInterface $businessManager
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getConnectionToken(Business $business, BusinessManagerInterface $businessManager)
    {
        return Response::json([
            'secret' => $businessManager->createStripeConnectionToken($business),
        ]);
    }
}