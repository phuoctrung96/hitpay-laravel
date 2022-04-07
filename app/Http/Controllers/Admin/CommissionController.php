<?php

namespace App\Http\Controllers\Admin;

use App\Business;
use App\Business\Commission;
use App\Enumerations\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessOutgoingFast;
use App\Jobs\SendExportedCommissionsToAdmin;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CommissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    public function index(Request $request)
    {
        $commissions = Commission::with([
            'business' => function (Relation $query) {
                $query->withTrashed();
            },
        ])->where('payment_provider', PaymentProvider::DBS_SINGAPORE);

        $status = $request->get('status');
        $status = strtolower($status);

        if ($status === 'succeeded') {
            $commissions->whereIn('status', [
                'succeeded',
                'succeeded_manually'
            ]);
        } else {
            $commissions->where('status', 'request_pending');
            $status = 'pending';
        }

        $paginator = $commissions->orderByDesc('id')->paginate();

        $paginator->appends('status', $status);

        return Response::view('admin.commission', compact('paginator', 'status'));
    }

    public function indexByBusiness(Request $request, Business $business)
    {
        $commissions = $business->commissions()->where('payment_provider', PaymentProvider::DBS_SINGAPORE);
        $paginator = $commissions->orderByDesc('id')->paginate();

        return Response::view('admin.business.commission', compact('business', 'paginator'));
    }

    public function get(Commission $commission)
    {
        $commission->load([
            'business' => function (Relation $query) {
                $query->withTrashed();
            },
        ], 'charges');

        $bank_lists = Business\Transfer::$availableBankSwiftCodes;

        return Response::view('admin.commission-single', compact('commission', 'bank_lists'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business\Commission $commission
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, Commission $commission)
    {
        if ($commission->status !== 'request_pending') {
            throw ValidationException::withMessages([
                'bank_swift_code' => 'The transfer of commission has been completed',
            ]);
        }

        if ($request->has('manual_transferred') && $request->get('manual_transferred') === '1') {
            $commission->status = 'succeeded_manually';
            $commission->save();

            Session::flash('success_message', 'The transfer of commission has been marked as manual transferred.');
        } else {
            $data = $this->validate($request, [
                'bank_swift_code' => [
                    'required',
                    Rule::in(array_keys(Business\Transfer::$availableBankSwiftCodes)),
                ],
                'bank_account_no' => 'required|string|max:32',
            ]);

            $commission->payment_provider_account_id = $data['bank_swift_code'].'@'.$data['bank_account_no'];
            $commission->save();

            ProcessOutgoingFast::dispatch($commission);

            Session::flash('success_message', 'The transfer of commission is updated and being processed now.');
        }

        return Response::redirectToRoute('admin.commission.get', $commission->getKey());
    }

    /**
     * @param \App\Business\Commission $commission
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function trigger(Commission $commission)
    {
        ProcessOutgoingFast::dispatch($commission);

        Session::flash('success_message', 'The transfer of commission is being processed now.');

        return Response::redirectToRoute('admin.commission.get');
    }

    public function export(Request $request)
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

        SendExportedCommissionsToAdmin::dispatch($data, $request->user());

        return Response::json([
            'success' => true,
        ]);
    }
}
