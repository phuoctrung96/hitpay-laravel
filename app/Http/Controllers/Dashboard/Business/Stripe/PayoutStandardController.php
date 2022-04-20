<?php

namespace App\Http\Controllers\Dashboard\Business\Stripe;

use App\Actions\Business\Payout\Stripe\Retrieve;
use App\Business;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class PayoutStandardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \ReflectionException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function index(Request $request, Business $business): \Illuminate\Http\Response
    {
        Gate::inspect('view', $business)->authorize();

        $actionData = Retrieve::withBusiness($business)->process();

        $data = $actionData['data'] ?? null;

        $provider = $actionData['provider'] ?? null;

        return Response::view('dashboard.business.stripe.payout-standard',
            compact('business', 'data', 'provider'));
    }
}
