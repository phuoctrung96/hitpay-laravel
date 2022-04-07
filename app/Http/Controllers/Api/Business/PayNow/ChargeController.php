<?php

namespace App\Http\Controllers\Api\Business\PayNow;

use App\Business;
use App\Business\Charge;
use App\Enumerations\Business\Channel;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\CurrencyCode;
use App\Enumerations\PaymentProvider;
use App\Exceptions\HitPayLogicException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\PaymentIntent;
use HitPay\PayNow\Generator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ChargeController extends Controller
{
    /**
     * ChargeController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Create PayNow payment intent.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business\PaymentIntent
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function createPaymentIntent(Request $request, Business $business)
    {
        Gate::inspect('operate', $business)->authorize();

        $paymentProvider = $business->paymentProviders()->where('payment_provider', PaymentProvider::DBS_SINGAPORE)
            ->first();

        if (!$paymentProvider) {
            App::abort(403, 'You have to setup PayNow before you can continue.');
        }

        $data = $this->validate($request, [
            'customer_id' => [
                'nullable',
                Rule::exists('business_customers', 'id')->where('business_id', $business->getKey()),
            ],
            'currency' => [
                'required',
                'string',
                Rule::in([
                    CurrencyCode::SGD,
                ]),
            ],
            'amount' => [
                'required',
                'decimal:0,2',
            ],
            'remark' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $charge = new Charge;

        $charge->channel = Channel::POINT_OF_SALE;

        if (!empty($data['customer_id'])) {
            $charge->setCustomer($business->customers()->findOrFail($data['customer_id']), true);
        }

        $charge->payment_provider = $paymentProvider->payment_provider;
        $charge->payment_provider_account_id = $paymentProvider->payment_provider_account_id;
        $charge->payment_provider_charge_method = 'paynow_online';
        $charge->currency = $data['currency'];
        $charge->remark = $data['remark'] ?? null;
        $charge->amount = getRealAmountForCurrency($charge->currency, $data['amount'], function (string $currency) {
            throw new HitPayLogicException(sprintf('The currency [%s] is invalid.', $currency));
        });

        $charge->status = ChargeStatus::REQUIRES_PAYMENT_METHOD;

        $paynow = Generator::new()
            ->setAmount($charge->amount)
            ->setExpiryAt(Date::now()->addSeconds(300))
            ->setMerchantName($business->getName());

        $paymentIntent = DB::transaction(function () use ($business, $paymentProvider, $charge, $paynow) {
            $business->charges()->save($charge);

            return $charge->paymentIntents()->create([
                'business_id' => $charge->business_id,
                'payment_provider' => PaymentProvider::DBS_SINGAPORE,
                'payment_provider_object_type' => 'inward_credit_notification',
                'payment_provider_object_id' => $paynow->getReference(),
                'payment_provider_method' => 'paynow_online',
                'currency' => $charge->currency,
                'amount' => $charge->amount,
                'status' => 'pending',
                'data' => [
                    'data' => $paynow->generate(),
                ],
                'expires_at' => Date::now()->addMinutes(15),
            ]);
        });

        return new PaymentIntent($paymentIntent);
    }
}
