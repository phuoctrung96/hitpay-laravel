<?php

namespace App\Http\Controllers\Api\Business\Stripe;

use App\Business;
use App\Http\Controllers\Controller;
use HitPay\Stripe\Payout;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

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
     * Get the payouts list for business Stripe account.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function __invoke(Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $paymentProvider = $business->paymentProviders()
            ->where('payment_provider', $paymentProviderName ?? $business->payment_provider)
            ->first();

        if (!$paymentProvider) {
            App::abort(403, 'You have to setup Stripe account before you can continue.');
        }

        $payouts = Payout::new($paymentProvider->payment_provider, $paymentProvider->payment_provider_account_id)
            ->index();

        $data = [];

        foreach ($payouts as $payout) {
            $data[] = [
                'id' => $payout->id,
                'type' => $payout->type,
                'currency' => $payout->currency,
                'amount' => getReadableAmountByCurrency($payout->currency, $payout->amount),
                'description' => $payout->description === 'STRIPE PAYOUT' ? 'Stripe Payout' : $payout->description,
                'status' => $payout->status,
                'source_type' => $payout->source_type,
                'created_date' => Date::createFromTimestamp($payout->created)->toAtomString(),
                'arrival_date' => $payout->arrival_date
                    ? Date::createFromTimestamp($payout->arrival_date)->toAtomString()
                    : null,
            ];
        }

        return Response::json($data);
    }
}
