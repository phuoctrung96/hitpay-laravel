<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $merchants = $user->businessPartner->businesses()
            ->paginate();
        $business = $user->businessPartner->business;

        $commissions = $user->businessPartner->commission()
            ->paginate();

        return view('dashboard.partner.index', compact('merchants', 'business', 'commissions'));
    }
}
