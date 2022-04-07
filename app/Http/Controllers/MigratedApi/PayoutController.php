<?php

namespace App\Http\Controllers\MigratedApi;

use HitPay\Stripe\Payout;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;

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
     * Get Stripe payouts list.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Support\Collection
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function index(Request $request)
    {
        $business = $this->getBusiness($request);

        Gate::inspect('view', $business)->authorize();

        /**
         * @var \App\Business\PaymentProvider $paymentProvider
         */
        $paymentProvider = $business->paymentProviders->where('payment_provider', 'stripe_sg')->first();

        $payouts = Payout::new($paymentProvider->payment_provider, $paymentProvider->payment_provider_account_id)
            ->index();

        $data = [];

        foreach ($payouts as $payout) {
            /**
             * @var \Stripe\Payout $payout
             */
            $data[] = [
                'id' => $payout->id,
                'type' => $payout->type,
                'currency' => $payout->currency,
                'amount' => getReadableAmountByCurrency($payout->currency, $payout->amount),
                'description' => $payout->description === 'STRIPE PAYOUT' ? 'Stripe Payout' : $payout->description,
                'status' => $payout->status,
                'source_type' => $payout->source_type,
                'created_date' => Date::createFromTimestamp($payout->created)->toDateString(),
                'arrival_date' => $payout->arrival_date
                    ? Date::createFromTimestamp($payout->arrival_date)->toDateString()
                    : null,
            ];
        }

        // We don't change the return type here, just follow what's in previous system.

        return Collection::make($data);
    }
}
