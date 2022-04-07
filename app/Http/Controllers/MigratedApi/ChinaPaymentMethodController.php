<?php

namespace App\Http\Controllers\MigratedApi;

use App\Enumerations\Business\Channel;
use App\Enumerations\Business\ChargeStatus;
use App\Exceptions\HitPayLogicException;
use HitPay\Stripe\Charge as StripeCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;

class ChinaPaymentMethodController extends TransactionController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param string $method
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function createSource(Request $request, string $method)
    {
        $business = $this->getBusiness($request);

        $paymentProvider = $business->paymentProviders()
            ->where('payment_provider', $business->payment_provider)->first();

        if (!in_array($method, [
            'alipay',
            'wechat',
        ])) {
            App::abort(404);
        }

        $data = $this->validate($request, [
            'currency_code' => 'required|in:SGD',
            'amount' => 'required|numeric|decimal:0,2',
            'remark' => 'nullable|string|max:255',
        ]);

        $charge = new \App\Business\Charge;

        $charge->channel = Channel::POINT_OF_SALE;
        $charge->currency = strtolower($data['currency_code']);
        $charge->remark = $data['remark'] ?? null;
        $charge->amount = getRealAmountForCurrency($charge->currency, $data['amount'], function (string $currency) {
            throw new HitPayLogicException(sprintf('The currency [%s] is invalid.', $currency));
        });
        $charge->status = ChargeStatus::REQUIRES_PAYMENT_METHOD;

        DB::transaction(function () use ($business, $charge) {
            $business->charges()->save($charge);
        });

        $charge = $charge->refresh();

        if ($method === 'alipay') {
            $sourceData['redirect'] = [
                'return_url' => URL::route('migrated-api.charge.callback', [
                    'id' => $charge->getKey(),
                    'method' => $method,
                    'business_id' => $business->getKey(),
                    'b_charge' => $charge->getKey(),
                ]),
            ];
        }

        $stripeSource = StripeCharge::new($paymentProvider->payment_provider)
            ->createSource($method, $charge->currency, $charge->amount,
                $business->statementDescription(), null, $sourceData ?? []);

        $paymentIntent = DB::transaction(function () use ($business, $paymentProvider, $charge, $stripeSource) {
            $metadata = $stripeSource->metadata->toArray();

            $metadata['charge_id'] = $charge->getKey();

            $stripeSource = StripeCharge::new($business->payment_provider)->updateSource($stripeSource->id, $metadata);

            return $charge->paymentIntents()->create([
                'business_id' => $charge->business_id,
                'payment_provider' => $paymentProvider->payment_provider,
                'payment_provider_account_id' => $paymentProvider->payment_provider_account_id,
                'payment_provider_object_type' => $stripeSource->object,
                'payment_provider_object_id' => $stripeSource->id,
                'payment_provider_method' => $stripeSource->type,
                'currency' => $stripeSource->currency,
                'amount' => $stripeSource->amount,
                'status' => $stripeSource->status,
                'data' => $stripeSource->toArray(),
                'expires_at' => Date::createFromTimestamp($stripeSource->created)->addHours(6),
            ]);
        });

        return Response::json([
            'transaction' => $this->generateTransactionObject($charge, $business),
            'source' => $stripeSource->toArray(),
        ]);
    }
}
