<?php

namespace App\Http\Controllers\Admin;

use App\Business;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class BusinessSetPaymentEnableController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Business $business): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'payment_enabled' => 'required|bool'
        ]);

        $business->payment_enabled = $data['payment_enabled'];
        $business->save();

        return Response::redirectTo(route('admin.business.show', ['business_id' => $business->getKey()]));
    }
}
