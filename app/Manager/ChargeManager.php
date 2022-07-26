<?php

namespace App\Manager;

use App\Business;
use App\Business\Charge;
use App\Business\Customer;
use App\Business\Order;
use App\Business\PaymentIntent;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\Business\PaymentMethodType;
use App\Http\Resources\Business\Charge as ChargeResource;
use App\Http\Resources\Business\PaymentIntent as PaymentIntentResource;
use App\Enumerations\Business\OrderStatus;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\Channel;
use App\Exceptions\HitPayLogicException;
use HitPay\Stripe\Charge as StripeCharge;
use HitPay\PayNow\Generator;
use Stripe\Stripe;
use Stripe\Terminal\ConnectionToken;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Business\PaymentRequest;

class ChargeManager extends AbstractManager implements ManagerInterface, ChargeManagerInterface
{
    public function getClass()
    {
        return Charge::class;
    }

    public function assignCustomer(Charge $charge, Customer $customer)
    {
        $charge->setCustomer($customer, true);
    }

    public function createRequiresPaymentMethod(Business $business, array $data) : Charge
    {
        $charge                                 = $this->createNew();
        $charge->channel                        = Channel::PAYMENT_GATEWAY;
        $charge->status                         = ChargeStatus::REQUIRES_PAYMENT_METHOD;
        $charge->currency                       = strtolower($data['currency']);
        $charge->plugin_provider                = $data['channel'] ?? $data['plugin_provider'];
        $charge->remark                         = $data['description'] ?? null;
        $charge->plugin_data                    = $data;
        $charge->is_successful_plugin_callback  = false;
        // always set plugin_provider_reference
        $charge->plugin_provider_reference      = $data['reference'];

        switch ($charge->plugin_provider) {
            case PluginProvider::CUSTOM:
            case PluginProvider::PRESTASHOP:
            case PluginProvider::APIWOOCOMMERCE:
            case PluginProvider::STORE:
            case PluginProvider::MAGENTO:
            case PluginProvider::XERO:
            case PluginProvider::PLATFORM:
            case PluginProvider::QUICKBOOKS:
            case PluginProvider::ECWID:
            case PluginProvider::OPENCART:
            case PluginProvider::LINK:
            case PluginProvider::INVOICE:
                    $charge->plugin_provider_reference = $data['reference'];
                    $charge->platform_business_id = $data['platform_business_id'] ?? null;
                    $charge->commission_rate = $data['commission_rate'] ?? 0;
                break;
            case PluginProvider::SHOPIFY:
                break;

            case PluginProvider::WOOCOMMERCE:
                    $charge->plugin_provider_reference  = $data['reference'];
                    $charge->plugin_provider_order_id   = $data['order_id'];
                break;
        }

        if (isset($data['customer_email']) && !isset($charge->customer_email)) {
            $charge->customer_email   = $data['customer_email'];
        }

        if (isset($data['customer_phone']) && !isset($charge->customer_phone)) {
            $charge->customer_phone_number   = substr($data['customer_phone'], 0, 32);
        }

        if (isset($data['customer_name']) && !isset($charge->customer_name)) {
            $charge->customer_name   = $data['customer_name'];
        }

        if (isset($data['order_id']) && !isset($charge->plugin_provider_order_id)) {
            $charge->plugin_provider_order_id   = $data['order_id'];
        }

        $charge->amount = getRealAmountForCurrency($charge->currency, $data['amount'], function (string $currency) {
            throw new HitPayLogicException(sprintf('The currency [%s] is invalid.', $currency));
        });

        DB::transaction(function () use ($business, $charge) {
            $business->charges()->save($charge);
        });

        $charge = $charge->refresh();

        return $charge;
    }

    public function captureStripePaymentIntent(Business $business, PaymentIntent $paymentIntent)
    {
        return StripeCharge::new($business->payment_provider)
            ->capturePaymentIntent($paymentIntent->payment_provider_object_id)
        ;
    }

    public function createCash(Business $business, Charge $charge) : ChargeResource
    {
        $charge->home_currency                  = $charge->currency;
        $charge->home_currency_amount           = $charge->amount;
        $charge->payment_provider               = 'hitpay';
        $charge->payment_provider_charge_method = PaymentMethodType::CASH;
        $charge->status                         = ChargeStatus::SUCCEEDED;
        $charge->closed_at                      = $charge->freshTimestamp();
        $target                                 = $charge->target;

        if ($target instanceof Order) {
            $target->status     = OrderStatus::COMPLETED;
            $target->closed_at  = $target->freshTimestamp();
        }

        DB::transaction(function () use ($charge, $target) {
            $charge->save();

            if ($target instanceof Order) {
                $target->save();
                $target->updateProductsQuantities();
                Artisan::queue('sync:hitpay-order-to-ecommerce --order_id=' . $target->id);
            }
        });

        return new ChargeResource($charge);
    }

    public function markAsCanceled(Charge $charge) : void
    {
        if ($charge->status === ChargeStatus::REQUIRES_PAYMENT_METHOD) {
            $charge->update([
                'status'    => ChargeStatus::CANCELED,
                'closed_at' => $charge->freshTimestamp(),
            ]);
        }
    }

    public function markAsSuccessfulPluginCallback(Charge $charge) : void
    {
        if ($charge->channel === Channel::PAYMENT_GATEWAY) {
            $charge->update([
                'is_successful_plugin_callback' => true
            ]);
        }
    }

    public function markAsFailedPluginCallback(Charge $charge) : void
    {
        if ($charge->channel === Channel::PAYMENT_GATEWAY) {
            $charge->update([
                'callback_url_status' => ChargeStatus::FAILED
            ]);
        }
    }

    public function updateAmount(Charge $charge, $amount) : void
    {
        $charge->update([
          'amount' => getRealAmountForCurrency($charge->currency, $amount)
        ]);
    }

    public function updateRemark(Charge $charge, $remark) : void
    {
        $charge->update([
            'remark' => $remark
        ]);
    }

    public function updateDataSignature(Charge $charge, $signature) : void
    {
        $data                        = $charge->plugin_data;
        $data['response_signature']  = $signature;
        $charge->update([
            'plugin_data' => $data
        ]);
    }

    public function incrementRetryCount(Charge $charge) : void
    {
        $charge->update([
            'callback_url_retry_count' => ($charge->callback_url_retry_count ?? 0) + 1,
        ]);
    }

    public function getFindByProviderReference($pluginProviderReference, $status) : ?Charge
    {
        return Charge::where('plugin_provider_reference', $pluginProviderReference)->where('status', $status)->first();
    }

    public function getFindByPluginProviderAndProviderReference($pluginProvider, $pluginProviderReference) : ?Charge
    {
        return Charge::where('plugin_provider', $pluginProvider)->where('plugin_provider_reference', $pluginProviderReference)->first();
    }

    public function getFindAllByPluginProviderAndProviderReference($pluginProvider, $pluginProviderReference)
    {
        return Charge::where('plugin_provider', $pluginProvider)->where('plugin_provider_reference', $pluginProviderReference)->get();
    }

    public function getFindChargesForUnsucessfulPaymentGateways()
    {
        return Charge::where('channel', Channel::PAYMENT_GATEWAY)
            ->where('status', 'succeeded')
            ->whereIn('plugin_provider', PluginProvider::GATEWAY_CHANNELS)
            ->where('is_successful_plugin_callback', false)
            ->where('callback_url_retry_count', '<', 4)
            ->get()
        ;
    }

    public function getFindChargesForUnsucessfulPaymentRequestsCallback()
    {
        return Charge::where('channel', Channel::PAYMENT_GATEWAY)
            ->where('status', 'succeeded')
            ->whereIn('plugin_provider', PluginProvider::API_CHANNELS)
            ->where('is_successful_plugin_callback', false)
            ->where('callback_url_retry_count', '<', 4)
            ->get()
        ;
    }

    public function generateShopifySignature(Charge $charge, $request)
    {
        $hmacSource = [];
        $signature  = $request->only([
            'x_account_id',
            'x_amount',
            'x_currency',
            'x_reference',
            'x_test'
        ]);

        foreach ($signature as $key => $val) {
            $hmacSource[$key] = "{$key}{$val}";
        }

        $hmacSource['x_result']             = "x_resultcompleted";
        $hmacSource['x_gateway_reference']  = "x_gateway_reference{$charge->getKey()}";
        $hmacSource['x_timestamp']          = "x_timestamp{$request->get('x_timestamp')}";

        ksort($hmacSource);

        $sig            = implode("", array_values($hmacSource));
        $calculatedHmac = hash_hmac('sha256', $sig, $request->get('api_key'));

        return $calculatedHmac;
    }

    public function generateShopifySignatureV2(Charge $charge, $request)
    {
        $hmacSource = [];
        $signature  = $request->only([
            'id',
            'amount',
            'timestamp'
        ]);

        foreach ($signature as $key => $val) {
            $hmacSource[$key] = "{$key}={$val}";
        }

        $hmacSource['result']             = "resultcompleted";
        $hmacSource['gateway_reference']  = "gateway_reference={$charge->getKey()}";

        ksort($hmacSource);

        $sig            = implode("", array_values($hmacSource));
        $calculatedHmac = hash_hmac('sha256', $sig, $request->get('api_key'));

        return $calculatedHmac;
    }

    public function generateSignature(Charge $charge, $request)
    {
        $hmacSource = [];
        $signature  = $request->only([
            'x_account_id',
            'x_amount',
            'x_currency',
            'x_reference',
            'x_test'
        ]);

        foreach ($signature as $key => $val) {
            $hmacSource[$key] = "{$key}{$val}";
        }

        $hmacSource['x_result']             = "x_resultcompleted";
        $hmacSource['x_gateway_reference']  = "x_gateway_reference{$charge->getKey()}";
        $hmacSource['x_timestamp']          = "x_timestamp{$request->get('x_timestamp')}";

        ksort($hmacSource);

        $sig            = implode("", array_values($hmacSource));
        $calculatedHmac = hash_hmac('sha256', $sig, $request->get('api_key'));

        return $calculatedHmac;
    }

    public function generateSignatureArray($secret, array $args)
    {
        $hmacSource = [];

        foreach ($args as $key => $val) {
            $hmacSource[$key] = "{$key}{$val}";
        }

        ksort($hmacSource);

        $sig            = implode("", array_values($hmacSource));
        $calculatedHmac = hash_hmac('sha256', $sig, $secret);

        return $calculatedHmac;
    }
}
