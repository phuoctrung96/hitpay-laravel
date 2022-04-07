<?php

namespace App\Http\Controllers\Dashboard\Business\Stripe;

use App\Actions\Business\Payout\Stripe\Retrieve;
use App\Business;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class PayoutController extends Controller
{
    /**
     * PayoutController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \ReflectionException
     */
    public function __invoke(Business $business): \Illuminate\Http\Response
    {
        Gate::inspect('view', $business)->authorize();

        $actionData = Retrieve::withBusiness($business)->process();

        $data = $actionData['data'] ?? null;

        $provider = $actionData['provider'] ?? null;

        return Response::view('dashboard.business.stripe.payout', compact('business', 'data', 'provider'));
    }
}
