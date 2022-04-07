<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Business\ApiKey;
use App\Http\Controllers\Controller;
use App\Manager\ApiKeyManager;
use App\Manager\BusinessManagerInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class OauthClientController extends Controller
{
    /**
     * ApiKeyController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Business $business,
        BusinessManagerInterface $businessManager
    )
    {
        Gate::inspect('view', $business)->authorize();

        $stripePublishableKey = $businessManager->getStripePublishableKey($business);

        return Response::view('dashboard.business.oauth-client.index', compact(
            'business','stripePublishableKey'
        ));
    }
}
