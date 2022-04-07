<?php

namespace App\Http\Controllers\MigratedApi;

use App\Enumerations\OrderStatus;
use App\Enumerations\TransactionStatus;
use App\Jobs\SendExportedCharges;
use App\Jobs\SendExportedOrders;
use App\Jobs\SendExportedTransactions;
use HitPay\Support\Controllers\StandardizeJsonResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    // use StandardizeJsonResponses;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function sendTransactionList(Request $request)
    {
        $business = $this->getBusiness($request);

        Gate::inspect('view', $business)->authorize();

        $data = $this->validate($request, [
            'year' => 'required|digits:4',
            'month' => 'required|numeric|between:1,12',
        ]);

        $date = Date::create($data['year'], $data['month']);

        SendExportedCharges::dispatch($business, [
            'from_date' => $date->startOfMonth()->toDateString(),
            'to_date' => $date->endOfMonth()->toDateString(),
        ]);

        return Response::json([
            'message' => 'The exported transactions will be sent to your email in a short while.',
        ]);
    }
}
