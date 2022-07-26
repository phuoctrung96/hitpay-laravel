<?php

namespace App\Http\Controllers\Api\Business;

use App\Business;
use App\Enumerations\Business\ChargeStatus;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExportChargesController extends Controller
{
    protected int $perPage = 100;

    /**
     * Get charges.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \ReflectionException
     */
    public function __invoke(Request $request)
    {
        $user = Facades\Auth::user();

        if (!$user instanceof User) {
            throw new NotFoundHttpException;
        }

        if ($request->headers->has('X-BUSINESS-API-KEY')) {
            $business = $user->businessesOwned()->first();
        } else {
            if (!$user->businesses->contains($request->business_id)) {
                Facades\App::abort(403, 'User doesnt have access to the business');
            }

            $business = Business::find($request->business_id);
        }

        $builder = $business->charges();

        $builder->with([
            'refunds',
            'paymentRequest',
        ]);
        $builder->where('status', ChargeStatus::SUCCEEDED);

        $after = $request->input('after');

        if (!is_null($after)) {
            if (Str::isUuid($after)) {
                $builder->where('id', '<', $after);
            } else {
                $builder->whereNull('id');
            }
        } else {
            $before = $request->input('before');

            if (!is_null($before)) {
                if (Str::isUuid($before)) {
                    $builder->where('id', '>', $before);
                } else {
                    $builder->whereNull('id');
                }
            }
        }

        $builder->orderBy('business_id', 'desc');
        $builder->orderBy('id', 'desc');

        $builder->limit($this->perPage);

        $charges = $builder->get();

        $data = [];

        foreach ($charges as $charge) {
            $refundedAmount = $charge->refunds
                ->where('is_cashback', 0)
                ->where('is_campaign_cashback', 0)
                ->sum('amount');

            $data[] = [
                'id' => $charge->getKey(),
                'quantity' => 1,
                'status' => $charge->status,
                'buyer_name' => $charge->customer_name ?? $charge->paymentRequest->name ?? null,
                'buyer_phone' => $charge->customer_phone_number ?? $charge->paymentRequest->phone ?? null,
                'buyer_email' => $charge->customer_email ?? $charge->paymentRequest->email ?? null,
                'currency' => $charge->currency,
                'amount' => getFormattedAmount($charge->currency, $charge->amount, false),
                'refunded_amount' => getFormattedAmount($charge->currency, $refundedAmount, false),
                'payment_type' => $charge->payment_provider_charge_method,
                'fees' => getFormattedAmount($charge->currency, $charge->getTotalFee(), false),
                'created_at' => $charge->created_at->format("Y-m-d\TH:i:s"),
                'updated_at' => $charge->updated_at->format("Y-m-d\TH:i:s"),
            ];
        }

        return Facades\Response::json(compact('data'));
    }
}
