<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Http\Controllers\Controller;
use App\Jobs\SendExportedTransfers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class CommissionController extends Controller
{
    /**
     * PayNowController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \ReflectionException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function payout(Business $business)
    {

        Gate::inspect('view', $business)->authorize();

        $commissions = $business->commissions()
            ->with('charges')
            ->where('payment_provider', PaymentProviderEnum::DBS_SINGAPORE)
            ->orderByDesc('id')->paginate();

        return Response::view('dashboard.business.commission', [
            'business' => $business,
            'paginator' => $commissions,
        ]);
    }

    /**
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \ReflectionException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function payoutShow(Business $business, Business\Commission $commission)
    {
        Gate::inspect('view', $business)->authorize();

        $commission->load('charges');

        return Response::view('dashboard.business.commission-show', [
            'business' => $business,
            'commission' => $commission,
        ]);
    }

    /**
     * Export commission for DBS payouts.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function export(Request $request, Business $business)
    {
        Gate::inspect('view', $business)->authorize();

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

        $charges = $business->commissions()->whereIn('payment_provider', [
            PaymentProviderEnum::DBS_SINGAPORE,
        ]);

        $fromDate = Date::parse($data['starts_at']);
        $toDate = Date::parse($data['ends_at']);

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $charges->whereDate('created_at', '>=', $fromDate->startOfDay()->toDateTimeString());
        $charges->whereDate('created_at', '<=', $toDate->endOfDay()->toDateTimeString());

        if ($charges->count() < 1) {
            App::abort(422, 'You don\'t any PayNow payout between these date.');
        }

        SendExportedTransfers::dispatch($business, [
            'from_date' => $data['starts_at'],
            'to_date' => $data['ends_at'],
        ]);

        return Response::json([
            'success' => true,
        ]);
    }
}
