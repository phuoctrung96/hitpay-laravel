<?php

namespace App\Http\Controllers\Admin;

use App\Business;
use App\Business\Transfer;
use App\Enumerations\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessOutgoingFast;
use App\Jobs\SendExportedFastPayoutsToAdmin;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FastPaymentTransferController extends Controller
{
    /**
     * FastPaymentTransferController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    public function index(Request $request)
    {
        $transfers = Transfer::with([
            'business' => function (Relation $query) {
                $query->withTrashed();
            },
        ])->where('payment_provider', PaymentProvider::DBS_SINGAPORE);

        $status = $request->get('status');
        $status = strtolower($status);

        if ($status === 'succeeded') {
            $transfers->whereIn('status', [
                'succeeded',
                'succeeded_manually',
            ]);
        } else {
            $transfers->where('status', 'request_pending');
            $status = 'pending';
        }

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            if ($keyword) {
                $transfers->where(function($query) use ($keyword) {
                    $query->where('id', $keyword);
                    $query->orWhere('business_id', $keyword);
                });
            }
        }

        $paginator = $transfers->orderByDesc('id')->paginate();

        $paginator->appends('status', $status);

        return Response::view('admin.fast-payment-transfer', compact('paginator', 'status'));
    }

    public function indexByBusiness(Request $request, Business $business)
    {
        $transfers = $business->transfers()->where('payment_provider', PaymentProvider::DBS_SINGAPORE);
        $paginator = $transfers->orderByDesc('id')->paginate();

        return Response::view('admin.business.fast-payment-transfer', compact('business', 'paginator'));
    }

    public function get(Transfer $transfer)
    {
        $transfer->load([
            'business' => function (Relation $query) {
                $query->withTrashed();
            },
        ], 'charges');

        $bank_lists = Transfer::$availableBankSwiftCodes;

        return Response::view('admin.fast-payment-transfer-single', compact('transfer', 'bank_lists'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business\Transfer $transfer
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, Transfer $transfer)
    {
        if ($transfer->status !== 'request_pending') {
            throw ValidationException::withMessages([
                'bank_swift_code' => 'The transfer has been completed',
            ]);
        }

        if ($request->has('manual_transferred') && $request->get('manual_transferred') === '1') {
            $transfer->status = 'succeeded_manually';
            $transfer->save();

            Session::flash('success_message', 'The fast transfer has been marked as manual transferred.');
        } else {
            $data = $this->validate($request, [
                'bank_swift_code' => [
                    'required',
                    Rule::in(array_keys(Transfer::$availableBankSwiftCodes)),
                ],
                'bank_account_no' => 'required|string|max:32',
            ]);

            $transfer->payment_provider_account_id = $data['bank_swift_code'].'@'.$data['bank_account_no'];
            $transfer->save();

            ProcessOutgoingFast::dispatch($transfer)->onQueue('main-server');

            Session::flash('success_message', 'The fast transfer is updated and being processed now.');
        }

        return Response::redirectToRoute('admin.transfer.fast-payment.get', $transfer->getKey());
    }

    /**
     * @param \App\Business\Transfer $transfer
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function trigger(Transfer $transfer)
    {
        ProcessOutgoingFast::dispatch($transfer)->onQueue('main-server');

        Session::flash('success_message', 'The fast transfer is being processed now.');

        return Response::redirectToRoute('admin.transfer.fast-payment.get');
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

        SendExportedFastPayoutsToAdmin::dispatch($data, $request->user());

        return Response::json([
            'success' => true,
        ]);
    }
}
