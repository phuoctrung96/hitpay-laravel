<?php

namespace App\Http\Controllers\MigratedApi;

use App\Business;
use App\Enumerations\Business\Channel;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\SupportedCurrencyCode;
use App\Enumerations\PaymentProvider;
use App\Exceptions\HitPayLogicException;
use Exception;
use HitPay\Stripe\Charge as StripeCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class PaymentCardController extends Controller
{
    /**
     * StripeTransactionController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function createPaymentIntent(Request $request)
    {
        $business = $this->getBusiness($request);

        [
            $data,
            $paymentProvider,
        ] = $this->validateForNewCharge($request, $business, null, [], [], [], SupportedCurrencyCode::listConstants());

        $charge = new Business\Charge;

        $charge->channel = Channel::POINT_OF_SALE;
        $charge->payment_provider = $paymentProvider->payment_provider;
        $charge->payment_provider_account_id = $paymentProvider->payment_provider_account_id;
        $charge->currency = strtolower($data['currency_code']);
        $charge->remark = $data['remark'] ?? null;
        $charge->amount = getRealAmountForCurrency($charge->currency, $data['amount'], function (string $currency) {
            throw new HitPayLogicException(sprintf('The currency [%s] is invalid.', $currency));
        });
        $charge->status = ChargeStatus::REQUIRES_PAYMENT_METHOD;

        $stripePaymentIntent = StripeCharge::new($charge->payment_provider)
            ->createPaymentIntent($paymentProvider->payment_provider_account_id, $charge->currency, $charge->amount,
                $business->statementDescription(), [
                    'remark' => $charge->remark,
                    'payment_method_types' => [
                        'card',
                    ],
                ]);

        try {
            /**
             * @var \App\Business\PaymentIntent $paymentIntent
             */
            $paymentIntent = DB::transaction(function () use (
                $business, $paymentProvider, $charge, $stripePaymentIntent
            ) {
                $business->charges()->save($charge);

                $metadata = $stripePaymentIntent->metadata->toArray();

                $metadata['charge_id'] = $charge->getKey();

                $stripePaymentIntent = StripeCharge::new($business->payment_provider)
                    ->updatePaymentIntent($stripePaymentIntent->id, $metadata);

                return $charge->paymentIntents()->create([
                    'business_id' => $charge->business_id,
                    'payment_provider' => $paymentProvider->payment_provider,
                    'payment_provider_account_id' => $paymentProvider->payment_provider_account_id,
                    'payment_provider_object_type' => $stripePaymentIntent->object,
                    'payment_provider_object_id' => $stripePaymentIntent->id,
                    'payment_provider_method' => $stripePaymentIntent->type,
                    'currency' => $stripePaymentIntent->currency,
                    'amount' => $stripePaymentIntent->amount,
                    'status' => $stripePaymentIntent->status,
                    'data' => $stripePaymentIntent->toArray(),
                ]);
            });
        } catch (Exception $exception) {
            $stripePaymentIntent->cancel();

            throw $exception;
        }

        return Response::json([
            'transaction_id' => $paymentIntent->business_charge_id,
            'payment_intent' => $paymentIntent->data['client_secret'],
            'url' => URL::route('migrated-api.payment-intent.process', [
                'id' => $paymentIntent->business_charge_id,
                'status' => 'processing',
            ]),
        ]);
    }

    /**
     * Helper to validate request for new charge.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param string|null $paymentProviderName
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @param array $currencies
     *
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     */
    protected function validateForNewCharge(
        Request $request, Business $business, string $paymentProviderName = null, array $rules = [],
        array $messages = [], array $customAttributes = [], array $currencies = []
    ) {
        switch ($business->payment_provider) {

            case PaymentProvider::STRIPE_MALAYSIA:
            case PaymentProvider::STRIPE_SINGAPORE:
                if (empty($currencies)) {
                    $currencies = [
                        strtoupper($business->currency),
                    ];
                }

                break;

            default:
                App::abort(403, 'Invalid payment provider. Please contact us.');
        }

        $currencies = array_map(function ($value) {
            return strtoupper($value);
        }, $currencies);

        $tryDetectCurrency = $request->get('currency');
        $tryDetectCurrency = strtolower($tryDetectCurrency);

        if ($tryDetectCurrency) {
            if (in_array($tryDetectCurrency, SupportedCurrencyCode::zeroDecimal())) {
                $tryValidateAmountRule = 'int';
            } elseif (in_array($tryDetectCurrency, SupportedCurrencyCode::normal())) {
                $tryValidateAmountRule = 'decimal:0,2';
            }
        }

        $data = $this->validate($request, $rules + [
                'currency_code' => [
                    'required',
                    'string',
                    Rule::in($currencies),
                ],
                'amount' => [
                    'required',
                    $tryValidateAmountRule ?? 'numeric',
                ],
                'remark' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ], $messages, $customAttributes);

        $paymentProvider = $business->paymentProviders()
            ->where('payment_provider', $paymentProviderName ?? $business->payment_provider)
            ->first();

        if (!$paymentProvider) {
            App::abort(403, 'You have to setup Stripe account before you can continue.');
        }

        return [
            $data,
            $paymentProvider,
        ];
    }
}
