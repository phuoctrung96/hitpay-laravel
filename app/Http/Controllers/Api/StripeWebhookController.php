<?php

namespace App\Http\Controllers\Api;

use App\Actions\Business\Stripe\Account\SyncFromWebhook;
use App\Actions\Business\Stripe\Charge\PaymentIntent\SyncWithSucceededPaymentIntent;
use App\Actions\Business\Stripe\Charge\Source\ConfirmUsingChargeableSource;
use App\Business\PaymentIntent;
use App\Business\PaymentIntent as PaymentIntentModel;
use App\Enumerations\Business\PaymentMethodType;
use App\Http\Controllers\Controller;
use Exception;
use HitPay\Data\Countries;
use HitPay\Data\PaymentProviders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Stripe;

class StripeWebhookController extends Controller
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

        if (!isset($stripeConfigs['endpoint_secret'])
            || blank($stripeConfigs['endpoint_secret'])
            || !isset($stripeConfigs['secret'])
            || blank($stripeConfigs['secret'])) {
            Log::critical("The configuration for Stripe '{$this->paymentProvider->getCountry()}' is not set.");

            return Response::json(null, 400);
        }

        $secretKey = $stripeConfigs['endpoint_secret'];

        Stripe\Stripe::setApiKey($stripeConfigs['secret']);

        try {
            if (App::isLocal()) {
                $requestedContentRaw = $request->getContent();
                $requestedContent = json_decode($requestedContentRaw, true);
                $jsonError = json_last_error();

                if ($requestedContent === null && JSON_ERROR_NONE !== $jsonError) {
                    throw new Exception("Invalid payload: {$requestedContentRaw} (json_last_error() was {$jsonError})");
                }

                $event = Stripe\Event::constructFrom($requestedContent);
            } else {
                $event = Stripe\Webhook::constructEvent(
                    $request->getContent(),
                    $request->header('STRIPE_SIGNATURE'),
                    $stripeConfigs['endpoint_secret']
                );
            }
        } catch (Stripe\Exception\UnexpectedValueException|Stripe\Exception\SignatureVerificationException $exception) {
            Log::critical("The event received is invalid. Error : {$exception->getMessage()}");

            return Response::json(null, 400);
        }

        // For source
        if (Str::startsWith($event->type, Stripe\Source::OBJECT_NAME)
            && $event->data->object->object === Stripe\Source::OBJECT_NAME) {
            $this->sourceEventHandler($event);
        }
        // For payment intent
        //
        elseif (Str::startsWith($event->type, Stripe\PaymentIntent::OBJECT_NAME)
            && $event->data->object->object === Stripe\PaymentIntent::OBJECT_NAME) {
            $this->paymentIntentEventHandler($event);
        }
        // Bankorh : I think this is not to update custom account
        //
        elseif ($event->type === Stripe\Event::ACCOUNT_UPDATED) {
            SyncFromWebhook::withData([
                'api_key' => $secretKey,
                'payment_provider' => $paymentProviderName,
            ])->withStripeEvent($event)->process();
        }

        return Response::json();
    }

    private function sourceEventHandler(Stripe\Event $event) : void
    {
        if (!in_array($event->data->object->type, [ 'alipay', 'wechat' ])) {
            return;
        }

        if ($event->type === Stripe\Event::SOURCE_CHARGEABLE) {
            $chargeableSource = Stripe\Source::retrieve($event->data->object->id);

            ( new ConfirmUsingChargeableSource )
                ->chargeableSource($chargeableSource, $this->paymentProvider->code)
                ->process();
        } elseif (in_array($event->type, [ Stripe\Event::SOURCE_FAILED, Stripe\Event::SOURCE_CANCELED ])) {
            $businessPaymentIntent = PaymentIntentModel::query()
                ->where('payment_provider', $this->paymentProvider->code)
                ->where('payment_provider_object_type', $event->data->object->object)
                ->where('payment_provider_object_id', $event->data->object->id)
                ->first();
 
            if ($businessPaymentIntent instanceof PaymentIntent) {
                $businessPaymentIntent->status = $event->data->object->status;

                $businessPaymentIntentData = $businessPaymentIntent->data;

                $businessPaymentIntentData['stripe']['source'] = $event->data->object->toArray();

                $businessPaymentIntent->data = $businessPaymentIntentData;

                $businessPaymentIntent->save();
            }
        }
    }

    private function paymentIntentEventHandler(Stripe\Event $event) : void
    {
        if ($event->type === Stripe\Event::PAYMENT_INTENT_SUCCEEDED) {
            $filename = $event->type.DIRECTORY_SEPARATOR.$event->data->object->metadata->charge_id.'.txt';

            Storage::append($filename, json_encode($event->data->object->toArray(), JSON_PRETTY_PRINT));

            $businessPaymentIntent = PaymentIntentModel::where('payment_provider', $this->paymentProvider->code)
                ->where('payment_provider_object_type', $event->data->object->object)
                ->where('payment_provider_object_id', $event->data->object->id)
                ->first();

            if (!( $businessPaymentIntent instanceof PaymentIntentModel )) {
                Log::info(
                    "The Stripe payment intent (ID : {$event->data->object->id}) isn't found, it might not come from this server."
                );
            } elseif ($businessPaymentIntent->payment_provider_method === PaymentMethodType::GRABPAY ||
                      $businessPaymentIntent->payment_provider_method === PaymentMethodType::FPX) {
                SyncWithSucceededPaymentIntent::withBusinessPaymentIntent($businessPaymentIntent)->process();
            } else {
                Log::info(
                    "The payment intent (ID : {$businessPaymentIntent->getKey()}; Stripe ID : {$event->data->object->id}; Method : {$event->data->object->payment_method}) isn't handled because we handle payment intent for method Grabpay only in webhook."
                );
            }
        } elseif (in_array($event->type, [
            Stripe\Event::PAYMENT_INTENT_PAYMENT_FAILED,
            Stripe\Event::PAYMENT_INTENT_CANCELED,
        ])) {
            $businessPaymentIntent = PaymentIntentModel::query()
                ->where('payment_provider', $this->paymentProvider->code)
                ->where('payment_provider_object_type', $event->data->object->object)
                ->where('payment_provider_object_id', $event->data->object->id)
                ->first();

            $businessPaymentIntent->status = $event->data->object->status;

            $businessPaymentIntentData = $businessPaymentIntent->data;

            $businessPaymentIntentData['stripe']['payment_intent'] = $event->data->object->toArray();

            $businessPaymentIntent->data = $businessPaymentIntentData;

            $businessPaymentIntent->save();
        }
    }
}
