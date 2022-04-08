<?php

namespace App\Http\Controllers\Api\Business\PayNow;

use App\Business;
use App\Enumerations\PaymentProvider;
use App\Helpers\Pagination;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Transfer;
use Illuminate\Support\Facades\Gate;
use \Illuminate\Http\Request;

class PayoutController extends Controller
{
    /**
     * PayoutController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Get transfer for DBS payouts.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function __invoke(Request $request, Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $paginateNumber = Pagination::getDefaultPerPage();

        if ($request->has('per_page')) {
            $paginateNumber = (int)$request->per_page;
        }

        $transfers = $business->setConnection('mysql_read')->transfers()->whereIn('payment_provider', [
            PaymentProvider::DBS_SINGAPORE,
        ])->paginate($paginateNumber);

        return Transfer::collection($transfers);
    }
}
