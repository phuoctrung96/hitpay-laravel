<?php

namespace App\Http\Controllers\Admin;

use App\Business;
use App\Enumerations\Business\ChargeStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendExportedCharges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Response;

class BusinessChargeController extends Controller
{
    /**
     * BusinessChargeController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
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
        $paginator = $business->charges()->with('target');

        $status = $request->get('status');
        $status = strtolower($status);

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            if ($keyword) {
                $paginator->where('id', $keyword)
                    ->orWhere('business_target_id', $keyword)
                    ->orWhere('plugin_provider_reference', $keyword)
                    ->orWhere('customer_email', 'like', '%'.$keyword.'%');
            }
        } else {
            if ($status === 'refunded') {
                $paginator->whereIn('status', [
                    ChargeStatus::REFUNDED,
                    ChargeStatus::VOID,
                ]);
            } elseif ($status === 'failed') {
                $paginator->whereIn('status', [
                    ChargeStatus::FAILED,
                    ChargeStatus::CANCELED,
                ]);
            } else {
                $status = 'succeeded';

                $paginator->where('status', ChargeStatus::SUCCEEDED);
            }
        }

        $paginator = $paginator->orderByDesc('id')->paginate();

        if (isset($id)) {
            if (count($paginator->items()) === 1) {
                $status = $paginator->items()[0]->status;
            } else {
                $status = null;
            }
        } elseif (!$status) {
            $status = 'succeeded';
        }

        $paginator->appends('status', $status);

        return Response::view('admin.business.charge-index', compact('business', 'paginator', 'status'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function export(Request $request, Business $business)
    {
        $data = $this->validate($request, [
            'starts_at' => [
                'required',
                'date_format:Y-m-d',
            ],
            'ends_at' => [
                'required',
                'date_format:Y-m-d',
            ],
        ]);

        $startsAt = Date::parse($data['starts_at']);
        $endsAt = Date::parse($data['ends_at']);

        SendExportedCharges::dispatch($business, [
            'from_date' => $startsAt->startOfDay(),
            'to_date' => $endsAt->endOfDay(),
        ], $request->user());

        return Response::json([
            'success' => true,
        ]);
    }
}
