<?php

namespace App\Http\Controllers\Api;

use App\Actions\Business\Stripe\Account\SyncFromWebhook;
use App\Actions\Business\Stripe\Payouts;
use App\Actions\Exceptions\BadRequest;
use App\Business\PaymentProvider as PaymentProviderModel;
use App\Http\Controllers\Controller;
use Exception;
use HitPay\Data\Countries;
use HitPay\Data\PaymentProviders;
use HitPay\Stripe\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Stripe;
use Stripe\Account;
use Stripe\Event;

class StripeConnectWebhookController extends Controller
{
    private ?Countries\Objects\PaymentProvider $paymentProvider = null;

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $paymentProviderName
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function __invoke(Request $request, string $paymentProviderName)
    {
        $this->paymentProvider = PaymentProviders::all()
            ->where('official_code', 'stripe')
            ->where('code', $paymentProviderName)
            ->first();

        if (!( $this->paymentProvider instanceof Countries\Objects\PaymentProvider )) {
            Log::critical("The Stripe payment provider (Code : '{$paymentProviderName}') is not available.");

            return Response::json(null, 400);
        }

        $stripeConfigs = Config::get("services.stripe.{$this->paymentProvider->getCountry()}");

        if (!isset($stripeConfigs['endpoint_secret_connect'])
            || blank($stripeConfigs['endpoint_secret_connect'])
            || !isset($stripeConfigs['secret'])
            || blank($stripeConfigs['secret'])) {
            Log::critical("The configuration for Stripe '{$this->paymentProvider->getCountry()}' is not set.");

            return Response::json(null, 400);
        }

        Stripe\Stripe::setApiKey($stripeConfigs['secret']);

        try {
            if (App::isLocal()) {
                $requestedContentRaw = $request->getContent();
                $requestedContent = json_decode($requestedContentRaw, true);
                $jsonError = json_last_error();

                if ($requestedContent === null && JSON_ERROR_NONE !== $jsonError) {
                    throw new Exception(
                        "Invalid payload for connect: {$requestedContentRaw} (json_last_error() was {$jsonError})"
                    );
                }

                $event = Stripe\Event::constructFrom($requestedContent);
            } else {
                $event = Stripe\Webhook::constructEvent(
                    $request->getContent(),
                    $request->header('STRIPE_SIGNATURE'),
                    $stripeConfigs['endpoint_secret_connect']
                );
            }
        } catch (Stripe\Exception\UnexpectedValueException|Stripe\Exception\SignatureVerificationException $exception) {
            Log::critical("The event received for connect is invalid. Error : {$exception->getMessage()}");

            return Response::json(null, 400);
        }

        if (Str::startsWith($event->type, Stripe\Payout::OBJECT_NAME)
            && $event->data->object->object === Stripe\Payout::OBJECT_NAME) {
            $this->payoutEventHandler($event);
        } elseif ($event->type === Event::ACCOUNT_UPDATED) {
            if ($event->data->object->object !== 'account') {
                // If this happened, I have no idea what is this.

                return Response::json();
            }

            $provider = PaymentProviderModel::where('payment_provider_account_id', $event->data->object->id)->first();

            if (!$provider instanceof PaymentProviderModel) {
                return Response::json();
            }

            if ($provider->payment_provider_account_type === 'custom') {
                ( new SyncFromWebhook )->process($this->paymentProvider->code, $event->account);;
            } else {
                Customer::new($paymentProviderName);

                $account = Account::retrieve($event->data->object->id);

                $provider->data = $account->toArray();
                $provider->save();
            }
        } elseif ($event->type === Event::ACCOUNT_APPLICATION_DEAUTHORIZED) {
            if ($event->data->object->object !== 'account') {
                // If this happened, I have no idea what is this.

                return Response::json();
            }

            $provider = PaymentProviderModel::where('payment_provider_account_id', $event->data->object->id)->first();

            if (!$provider instanceof PaymentProviderModel) {
                return Response::json();
            }

            $business = $provider->business;

            Log::critical('The business `'.$business->name.'` [ID: '.$business->getKey().'] has de-authorized HitPay.');
        }

        return Response::json();
    }

    /**
     * Handle Stripe Payout.
     *
     * @param  \Stripe\Event  $event
     *
     * @return void
     * @throws \App\Actions\Exceptions\BadRequest
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function payoutEventHandler(Stripe\Event $event) : void
    {
        try {
            if ($event->type === Stripe\Event::PAYOUT_CREATED) {
                ( new Payouts\Store )
                    ->payout($this->paymentProvider->code, $event->account, $event->data->object->id)
                    ->process();
            } elseif ($event->type === Stripe\Event::PAYOUT_PAID) {
                ( new Payouts\Update )
                    ->payout($this->paymentProvider->code, $event->account, $event->data->object->id)
                    ->process();
            } elseif ($event->type === Stripe\Event::PAYOUT_FAILED) {
                ( new Payouts\HandleFailed )
                    ->payout($this->paymentProvider->code, $event->account, $event->data->object->id)
                    ->process();
            }
        } catch (BadRequest $exception) {
            if (!$exception->canBeIgnored()) {
                throw $exception;
            }

            Log::notice($exception->getMessage());
        }
    }
}
