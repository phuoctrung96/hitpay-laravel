<?php

namespace App\Http\Controllers\Dashboard\Business\Stripe;

use App\Business;
use App\Helpers\Pagination;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use App\Actions\Business\Payout\Stripe\RetrieveCustomConnect;

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
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function __invoke(Request $request, Business $business): \Illuminate\Http\Response
    {
        Gate::inspect('view', $business)->authorize();

        $perPage = $request->get('perPage', Pagination::getDefaultPerPage());

        $actionData = RetrieveCustomConnect::withBusiness($business)->setPerPage($perPage)->process();

        $provider = $actionData['provider'] ?? null;

        $paginator = $actionData['transfers'] ?? null;

        return Response::view('dashboard.business.stripe.payout',
            compact('business', 'paginator', 'provider', 'perPage'));
    }
}
