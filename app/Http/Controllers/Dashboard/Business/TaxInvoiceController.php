<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;

use App\Enumerations\Business\ChargeStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Jobs\SendTaxInvoice;

class TaxInvoiceController extends Controller
{
    /**
     * TaxInvoiceController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request, Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $business_creation_date = Carbon::createFromFormat('Y-m-d H:i:s', $business->created_at);

        if (($diff = $business_creation_date->diffInMonths(now())) < 12){
            $result = now()->startOfMonth()->subMonths($diff)->monthsUntil(now());
        }else{
            $result = now()->startOfMonth()->subMonths(11)->monthsUntil(now());
        }

        $months = [];
        foreach ($result as $dt) {
            $months[] = ['name' => $dt->format("F"), 'value' => $dt->format("m"), 'year' => $dt->format("Y")];
        }

        return Response::view('dashboard.business.tax-invoice.index', compact('business', 'months'));
    }

    public function downloadInvoice(Request $request, Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $data = $this->validate($request, [
            'month' => 'required|numeric',
            'year' => 'required|numeric'
        ]);

        SendTaxInvoice::dispatch($business, $data);

        return Response::json([
            'success' => true
        ]);
    }
}
