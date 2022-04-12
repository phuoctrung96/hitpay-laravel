<?php

namespace App\Http\Controllers\Api\Business\Stripe;

use App\Actions\Business\Stripe\Charge\PaymentIntent\AttachPaymentMethod as PaymentIntentAttachPaymentMethod;
use App\Actions\Business\Stripe\Charge\PaymentIntent\Capture as PaymentIntentCapture;
use App\Actions\Business\Stripe\Charge\PaymentIntent\Confirm as PaymentIntentConfirm;
use App\Actions\Business\Stripe\Charge\PaymentIntent\Create as PaymentIntentCreate;
use App\Actions\Business\Stripe\Charge\Source\Create as SourceCreate;
use App\Actions\Exceptions\BadRequest;
use App\Business;
use App\Business\Charge;
use App\Enumerations\Business\Channel;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\SupportedCurrencyCode;
use App\Exceptions\HitPayLogicException;
use App\Http\Controllers\Api\Business\ChargeController as BaseController;
use App\Http\Resources\Business\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Stripe\Exception\CardException;
use Stripe\Stripe;
use Stripe\Terminal\ConnectionToken;

class ChargeController extends BaseController
{
    /**
     * Create a payment intent.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business\PaymentIntent|\Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function createPaymentIntent(Request $request, Business $business)
    {
        Gate::inspect('operate', $business)->authorize();

        [
            $data,
            $paymentProvider,
        ] = $this->validateForNewCharge($request, $business, null, [
            'card_present' => [
                'nullable',
                'bool',
            ],
        ], [], [], SupportedCurrencyCode::listConstants());

        if ($data['card_present'] ?? false) {
            $paymentMethod = 'card_present';
        }

        $charge = new Charge;

        $charge->channel = Channel::POINT_OF_SALE;

        if (!empty($data['customer_id'])) {
            $charge->setCustomer($business->customers()->findOrFail($data['customer_id']), true);
        }

        $charge->currency = $data['currency'];
        $charge->remark = $data['remark'] ?? null;
        $charge->amount = getRealAmountForCurrency($charge->currency, $data['amount'], function (string $currency) {
            throw new HitPayLogicException(sprintf('The currency [%s] is invalid.', $currency));
        });

        $charge->status = ChargeStatus::REQUIRES_PAYMENT_METHOD;

        $business->charges()->save($charge);

        try {
            $paymentIntent = PaymentIntentCreate::withBusiness($business)->businessCharge($charge)->data([
                'method' => $paymentMethod ?? 'card',
                'terminal_id' => $data['terminal_id'] ?? null
            ])->process();
        } catch (BadRequest $exception) {
            return Response::json([
                'error_message' => 'Transaction Failed. Please complete card payments setup under Settings > Payment Methods in your hitpay dashboard.',
            ], 400);
        }

        return new PaymentIntent($paymentIntent);
    }

    /**
     * Create WeChat Pay source.
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
    public function createWechatSource(Request $request, Business $business)
    {
        Gate::inspect('operate', $business)->authorize();

        [
            $data,
            $paymentProvider,
        ] = $this->validateForNewCharge($request, $business);

        $charge = new Charge;

        $charge->channel = Channel::POINT_OF_SALE;

        if (!empty($data['customer_id'])) {
            $charge->setCustomer($business->customers()->findOrFail($data['customer_id']), true);
        }

        $charge->currency = $data['currency'];
        $charge->remark = $data['remark'] ?? null;
        $charge->amount = getRealAmountForCurrency($charge->currency, $data['amount'], function (string $currency) {
            throw new HitPayLogicException(sprintf('The currency [%s] is invalid.', $currency));
        });

        $charge->status = ChargeStatus::REQUIRES_PAYMENT_METHOD;

        $business->charges()->save($charge);

        // Stripe Docs
        // URL: https://stripe.com/docs/sources/wechat-pay#sources-expiration
        //
        // Sources expiration
        // ------------------
        //
        // A WeChat Pay source must be charged within six hours of becoming chargeable, or before 23:45 China Standard
        // Time (GMT+8) due to Chinese government restrictions around settlement. If it is not, its status is
        // automatically transitioned to canceled and your integration receives a source.canceled webhook event. Once a
        // chargeable source is canceled, the customer’s authorized WeChat Pay payment is refunded automatically—no
        // money is moved into your account. For this reason, make sure the order is canceled on your end and the
        // customer is notified when you receive the source.canceled event.
        //
        // Additionally, pending sources are canceled after one hour if they are not used to authorize a payment,
        // ensuring that all sources eventually transition out of their pending state to the canceled state if they are
        // not used.
        //
        // BANKORH'S NOTE: As I know there's no API to cancel the source. Since it will expire, we just ignore it first
        // if anything failed here.
        //
        // TODO - KEEP IN VIEW

        $paymentIntent = SourceCreate::withBusiness($business)->businessCharge($charge)->data([
            'method' => 'wechat',
        ])->process();

        return new PaymentIntent($paymentIntent);
    }

    /**
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getConnectionToken(Business $business)
    {
        $provider = $business->paymentProviders()->where('payment_provider', $business->payment_provider)->first();

        if (!$provider) {
            // should fail la.
        }

        $locations = $business->stripeTerminalLocations;

        $location = $locations->first();

        // Set your secret key. Remember to switch to your live secret key in production!
        // See your keys here: https://dashboard.stripe.com/account/apikeys
        Stripe::setApiKey(Config::get('services.stripe.sg.secret'));
        if (!$location) {
            // BT reader do NOT need locations
            $token = ConnectionToken::create([]);
            return Response::json([
                'secret' => $token->secret,
            ]);
        }

        // In a new endpoint on your server, create a ConnectionToken and return the
        // `secret` to your app. The SDK needs the `secret` to connect to a reader.
        $token = ConnectionToken::create([
            'location' => $location->stripe_terminal_location_id,
        ]);

        return Response::json([
            'secret' => $token->secret,
        ]);
    }

    /**
     * Attach payment method to the payment intent or confirm the payment intent.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Business  $business
     * @param  string  $paymentIntentId
     *
     * @return \App\Http\Resources\Business\PaymentIntent|\Illuminate\Http\JsonResponse
     * @throws \App\Actions\Exceptions\BadRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function confirmPaymentIntent(Request $request, Business $business, string $paymentIntentId)
    {
        Gate::inspect('operate', $business)->authorize();

        $paymentIntent = $business->paymentIntents()->findOrFail($paymentIntentId);

        try {
            if ($request->has('payment_method_id')) {
                $paymentIntent = PaymentIntentAttachPaymentMethod::withBusinessPaymentIntent($paymentIntent)->data([
                    'payment_method' => $request->input('payment_method_id'),
                ])->process();
            } else {
                $paymentIntent = PaymentIntentConfirm::withBusinessPaymentIntent($paymentIntent)->process();
            }
        } catch (CardException $exception) {
            return Response::json([
                'error' => $exception->getDeclineCode(),
                'error_message' => $exception->getMessage(),
            ], 400);
        }

        return new PaymentIntent($paymentIntent);
    }

    /**
     * Capture the payment intent, for "card_present" only.
     *
     * @param  \App\Business  $business
     * @param  string  $paymentIntentId
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function capturePaymentIntent(Business $business, string $paymentIntentId)
    {
        Gate::inspect('operate', $business)->authorize();

        $paymentIntentModel = $business->paymentIntents()->findOrFail($paymentIntentId);

        $paymentIntentModel = PaymentIntentCapture::withBusinessPaymentIntent($paymentIntentModel)->process();

        return Response::json($paymentIntentModel->data['stripe']['payment_intent']);
    }
}
