<?php

namespace App\Http\Controllers\Api\Business;

use App\Actions\Business\Stripe\Charge\PaymentIntent\Create as PaymentIntentCreate;
use App\Actions\Business\Stripe\Charge\Source;
use App\Actions\Exceptions\BadRequest;
use App\Business;
use App\Business\Charge as ChargeModel;
use App\Business\Order;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\OrderStatus;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\CountryCode;
use App\Enumerations\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Charge;
use App\Http\Resources\Business\PaymentIntent;
use App\Logics\Business\ChargeRepository;
use App\Manager\FactoryPaymentIntentManagerInterface as PaymentIntentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderChargeController extends Controller
{
    /**
     * OrderChargeController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Order $order
     *
     * @return \App\Http\Resources\Business\Charge
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function createCharge(Request $request, Business $business, Order $order)
    {
        Gate::inspect('operate', $business)->authorize();

        if ($order->isDraft()) {
            $order->checkout();
        }

        if (!$order->requiresPaymentMethod()) {
            App::abort(403, 'You can\'t charge a non-requires payment method order.');
        } elseif ($order->isLinkSent()) {
            App::abort(403, 'You can\'t charge a link sent order.');
        }

        if ($business->country === CountryCode::SINGAPORE) {
            $methods = ChargeRepository::$hitPayMethods;
        } else {
            $methods = Arr::except(ChargeRepository::$hitPayMethods, 'paynow');
        }

        $data = $this->validate($request, [
            'method' => [
                'required',
                'string',
                Rule::in($methods),
            ],
        ]);

        $charge = new ChargeModel;

        $freshTimestamp = $charge->freshTimestamp();

        $charge->payment_provider = 'hitpay';
        $charge->payment_provider_charge_method = $data['method'];
        $charge->status = ChargeStatus::SUCCEEDED;
        $charge->closed_at = $freshTimestamp;

        $charge->setChargeable($order);

        $order->status = OrderStatus::COMPLETED;
        $order->closed_at = $freshTimestamp;

        $charge = DB::transaction(function () use ($business, $order, $charge) {
            $order->save();
            $order->updateProductsQuantities();
            $order->notifyAboutNewOrder();
            Artisan::queue('sync:hitpay-order-to-ecommerce --order_id=' . $order->id);

            return $business->charges()->save($charge);
        });

        $charge = $charge->refresh();

        return new Charge($charge);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Order $order
     *
     * @return \App\Http\Resources\Business\PaymentIntent|\Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function createPaymentIntent(Request $request, Business $business, Order $order, string $method = 'card')
    {
        if (!in_array($method, ['card', 'grabpay', 'card_present'])) {
            throw new NotFoundHttpException;
        }

        Gate::inspect('operate', $business)->authorize();

        $paymentProvider = $business->paymentProviders()
            ->where('payment_provider', $paymentProviderName ?? $business->payment_provider)
            ->first();

        if (!$paymentProvider) {
            App::abort(403, 'You have to setup Stripe account before you can continue.');
        }

        if ($order->isDraft()) {
            $order->checkout();
        }

        if (!$order->requiresPaymentMethod()) {
            App::abort(403, 'You can\'t charge a non-requires payment method order.');
        } elseif ($order->isLinkSent()) {
            App::abort(403, 'You can\'t charge a link sent order.');
        }

        $charge = new ChargeModel;

        $charge->payment_provider = $paymentProvider->payment_provider;
        $charge->payment_provider_account_id = $paymentProvider->payment_provider_account_id;
        $charge->status = ChargeStatus::REQUIRES_PAYMENT_METHOD;

        $charge->setChargeable($order);

        $business->charges()->save($charge);

        try {
            $paymentIntent = PaymentIntentCreate::withBusiness($business)->businessCharge($charge)->data([
                'method' => $method,
                'remark' => $charge->remark
            ])->process();
        } catch (BadRequest $exception) {
            return Response::json([
                'error_message' => $exception->getMessage(),
            ], 400);
        }

        return new PaymentIntent($paymentIntent);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Order $order
     *
     * @return \App\Http\Resources\Business\PaymentIntent
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function createWechatSource(Request $request, Business $business, Order $order)
    {
        Gate::inspect('operate', $business)->authorize();

        $paymentProvider = $business->paymentProviders()
            ->where('payment_provider', $paymentProviderName ?? $business->payment_provider)
            ->first();

        if (!$paymentProvider) {
            App::abort(403, 'You have to setup Stripe account before you can continue.');
        }

        if ($order->isDraft()) {
            $order->checkout();
        }

        if (!$order->requiresPaymentMethod()) {
            App::abort(403, 'You can\'t charge a non-requires payment method order.');
        } elseif ($order->isLinkSent()) {
            App::abort(403, 'You can\'t charge a link sent order.');
        }

        $charge = new ChargeModel();

        $charge->payment_provider = $paymentProvider->payment_provider;
        $charge->payment_provider_account_id = $paymentProvider->payment_provider_account_id;
        $charge->payment_provider_charge_method = 'wechat';
        $charge->status = ChargeStatus::REQUIRES_PAYMENT_METHOD;

        $charge->setChargeable($order);

        $paymentIntent = Source\Create::withBusiness($business)->businessCharge($charge)->data([
            'method' => $charge->payment_provider_charge_method,
            'metadata' => [
                'order_id' => $order->getKey(),
            ],
        ])->process();

        return new PaymentIntent($paymentIntent);
    }

    public function createPayNowPaymentIntent(
        PaymentIntentManager $paymentIntentManager, Business $business, Order $order
    ) {
        Gate::inspect('operate', $business)->authorize();

        $paymentProvider = $business->paymentProviders()
            ->where('payment_provider', PaymentProvider::DBS_SINGAPORE)
            ->first();

        if (!$paymentProvider) {
            App::abort(403, 'You have to setup PayNow before you can continue.');
        }

        if ($order->isDraft()) {
            $order->checkout();
        }

        if (!$order->requiresPaymentMethod()) {
            App::abort(403, 'You can\'t charge a non-requires payment method order.');
        } elseif ($order->isLinkSent()) {
            App::abort(403, 'You can\'t charge a link sent order.');
        }

        $charge = new ChargeModel;

        $charge->payment_provider = $paymentProvider->payment_provider;
        $charge->payment_provider_account_id = $paymentProvider->payment_provider_account_id;
        $charge->payment_provider_charge_method = 'wechat';
        $charge->status = ChargeStatus::REQUIRES_PAYMENT_METHOD;

        $charge->setChargeable($order);

        try {
            return $paymentIntentManager->create(PaymentMethodType::PAYNOW)->create($charge, $business);
        } catch (BadRequest $exception) {
            return Response::json([
                'error_message' => $exception->getMessage(),
            ], 400);
        }
    }
}
