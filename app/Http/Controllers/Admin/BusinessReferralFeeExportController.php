<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendExportedBusinessReferralFeesToAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class BusinessReferralFeeExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->validate($request, [
            'starts_at' => [
                'required',
                'date_format:Y-m-d',
            ],
            'ends_at' => [
                'required',
                'date_format:Y-m-d',
            ],
        ]);

        SendExportedBusinessReferralFeesToAdmin::dispatch($request->input('starts_at'), $request->input('ends_at'));

        return Response::json([
            'success' => true,
        ]);
    }
}
