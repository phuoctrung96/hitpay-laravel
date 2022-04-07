<?php

namespace App\Http\Controllers\Admin;

use App\Business;
use App\Http\Controllers\Controller;
use App\Logics\BusinessRepository;
use Illuminate\Support\Facades\Response;

class BusinessPaymentProviderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    /**
     * Deauthorize Stripe account from our side.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deauthorizeStripeSingaporeAccount(Business $business)
    {
        BusinessRepository::removeStripeSingaporeAccount($business);

        return Response::redirectToRoute('admin.business.show', [
            'business_id' => $business->getKey(),
        ]);
    }
}
