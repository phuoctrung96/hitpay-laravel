<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Enumerations\CountryCode;
use App\Http\Controllers\Controller;
use App\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades;

class PayoutController extends Controller
{
    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function index(Request $request, Business $business): \Illuminate\Http\RedirectResponse
    {
        if ($business->country === CountryCode::SINGAPORE) {
            return Facades\Response::redirectToRoute('dashboard.business.payment-provider.paynow.payout',
                $business->getKey());
        }

        if ($business->country === CountryCode::MALAYSIA) {
            // if FPX ready then redirect to fpx
            return Facades\Response::redirectToRoute('dashboard.business.payment-provider.stripe.payout',
                $business->getKey());
        }

        throw new \Exception("There is business with not yet available for \n
            payout with {$business->getKey()} and country {$business->country}");
    }
}
