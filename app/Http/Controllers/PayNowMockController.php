<?php

namespace App\Http\Controllers;

use App\Business;
use App\Business\Order;
use App\Business\PaymentIntent;
use App\Business\Refund;
use App\Business\RefundIntent;
use App\Enumerations\Business\Channel;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\OrderStatus;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\CurrencyCode;
use App\Enumerations\PaymentProvider;
use App\Exceptions\HitPayLogicException;
use Exception;
use HitPay\PayNow\Generator;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PayNowMockController extends Controller
{
    public function show(string $hash)
    {
        $data['url'] = URL::route('paynow.mock.process', compact('hash'));

        [
            $data['intent'],
            $data['business'],
            $data['charge'],
        ] = $this->getPaymentIntentAndBusiness($hash);

        return Response::view('paynow-mock', $data);
    }

    public function process(string $hash)
    {
        /**
         * @var \App\Business $business
         */
        [
            $intent,
            $business,
            $charge,
        ] = $this->getPaymentIntentAndBusiness($hash);

        if ($intent instanceof PaymentIntent) {
            /** @var \App\Business\Charge $charge */
            $charge = $intent->charge;

            if ($charge->platform_business_id) {
                $platformModel = Business::find($charge->platform_business_id);

                if ($platformModel) {
                    $paymentProviderModel = $platformModel->paymentProviders()
                        ->where('payment_provider', PaymentProvider::DBS_SINGAPORE)
                        ->first();

                    if ($paymentProviderModel) {
                        $hasPlatformProvider = true;
                    } else {
                        Log::critical(sprintf('Platform ID "%s" provided but no payment provider found.',
                            $charge->platform_business_id));
                    }
                } else {
                    Log::critical(sprintf('Platform ID "%s" provided but no business found.',
                        $charge->platform_business_id));
                }
            }

            /** @var \App\Business\PaymentProvider $paymentProviderModel */
            $paymentProviderModel = $paymentProviderModel ?? $business->paymentProviders()
                    ->where('payment_provider', PaymentProvider::DBS_SINGAPORE)->first();

            if ($intent->status === 'succeeded') {
                return Response::redirectToRoute('paynow.mock.page', compact('hash'));
            }

            $intent->status = 'succeeded';

            $data = $intent->data;

            $content['mocked'] = true;

            $data['response'] = $content;

            $intent->data = $data;

            $charge->payment_provider = $intent->payment_provider;
            $charge->payment_provider_account_id = $intent->payment_provider_account_id;
            $charge->payment_provider_charge_type = $intent->payment_provider_object_type;
            $charge->payment_provider_charge_id = $intent->payment_provider_object_id;
            $charge->payment_provider_charge_method = $intent->payment_provider_method;
            $charge->payment_provider_transfer_type = 'wallet';
            $charge->status = ChargeStatus::SUCCEEDED;
            $charge->data = $content;
            $charge->closed_at = $charge->freshTimestamp();

            if ($paymentProviderModel) {
                [
                    $fixedAmount,
                    $percentage,
                ] = $paymentProviderModel->getRateFor(
                    $business->country, $business->currency, $charge->currency, $charge->channel,
                    $charge->payment_provider_charge_method, null, null, $charge->amount
                );

                $charge->home_currency = $charge->currency;
                $charge->home_currency_amount = $charge->amount;
                $charge->exchange_rate = 1;
                $charge->fixed_fee = $fixedAmount;
                $charge->discount_fee_rate = $percentage;
                $charge->discount_fee = bcmul($charge->discount_fee_rate, $charge->home_currency_amount);

                if ($hasPlatformProvider ?? false) {
                    $charge->commission_amount = bcmul($charge->commission_rate, $charge->amount);
                    $charge->home_currency_commission_amount =
                        bcmul($charge->commission_rate, $charge->home_currency_amount);
                }
            }

            $targetModel = $charge->target;

            if ($targetModel instanceof Order) {
                if ($targetModel->channel === Channel::POINT_OF_SALE) {
                    $targetModel->status = OrderStatus::COMPLETED;
                    $targetModel->closed_at = $targetModel->freshTimestamp();
                } else {
                    $targetModel->status = OrderStatus::REQUIRES_BUSINESS_ACTION;
                }
            }
        } elseif ($intent instanceof RefundIntent) {
            if (!Str::startsWith($intent->payment_provider_account_id, 'proxy:')) {
                throw new Exception('Invalid refund intent without prefix proxy.');
            }

            $intent->status = 'succeeded';

            $data = $intent->data;

            $content['mock'] = true;

            $data['response'] = $content;

            $intent->data = $data;

            $balance = $charge->balance ?? $charge->amount;

            if ($balance - $intent->amount === 0) {
                $charge->status = ChargeStatus::REFUNDED;
                $charge->balance = null;
                $charge->closed_at = $charge->freshTimestamp();
            } elseif ($balance - $intent->amount < 0) {
                throw new Exception('The amount must be lesser than '.getFormattedAmount($charge->currency,
                        $balance).'.');
            } else {
                $charge->balance = $balance - $intent->amount;
            }

            $refund = new Refund;
            $refund->business_charge_id = $charge->getKey();
            $refund->payment_provider = $intent->payment_provider;
            $refund->payment_provider_account_id = $intent->payment_provider_account_id;
            $refund->payment_provider_refund_type = $intent->payment_provider_object_type;
            $refund->payment_provider_refund_id = $intent->payment_provider_object_id;
            $refund->payment_provider_refund_method = $intent->payment_provider_method;
            $refund->amount = $intent->amount;
            $refund->data = $intent->toArray();

            $intent->status = 'succeeded';

            DB::transaction(function () use ($charge, $refund, $intent) {
                $charge->save();
                $refund->save();
                $intent->save();

                return $charge;
            }, 3);
        } elseif ($intent instanceof Business\Wallet\TopUpIntent) {
            $intent->status = 'succeeded';

            $data = $intent->data;

            $content['mock'] = true;

            $data['response'] = $content;

            $intent->data = $data;
            $intent->status = 'succeeded';

            DB::transaction(function () use ($intent) {
                $intent->save();
            }, 3);

            $business->topUp($intent->currency, $intent->amount, 'Top up via PayNow Mock');

            return Response::redirectToRoute('paynow.mock.page', compact('hash'));
        } else {
            throw new Exception('Invalid type for $intent');
        }

        $targetModel = $targetModel ?? null;

        try {
            DB::transaction(function () use ($intent, $charge, $targetModel, $business) {
                $intent->save();
                $charge->save();

                if ($intent instanceof PaymentIntent && $targetModel instanceof Order) {
                    $targetModel->save();
                    $targetModel->updateProductsQuantities();
                    $targetModel->notifyAboutNewOrder();
                }

            }, 3);
        } catch (Throwable $exception) {
            throw $exception;
        }

        return Response::redirectToRoute('paynow.mock.page', compact('hash'));
    }

    private function getPaymentIntentAndBusiness(string $hash) : array
    {
        
        if (App::environment('production')) {
            App::abort(404);
        }

        try {
            $content = Crypt::decrypt($hash);
        } catch (DecryptException $exception) {
            App::abort(404);
        }
        //dd(Date::createFromFormat('YmdHis', $content['expiry_at'])->isPast());
        if (!isset($content['reference'], $content['amount'], $content['expiry_at'])) {
            App::abort(404);
        } elseif (Date::createFromFormat('YmdHis', $content['expiry_at'])->isPast()) {
            
            App::abort(403, 'The PayNow link is expired.');
        }

        if (Str::startsWith($content['reference'], 'DICNP')) {
            $intent = PaymentIntent::where('payment_provider', PaymentProvider::DBS_SINGAPORE)
                ->where('payment_provider_object_type', 'inward_credit_notification')
                ->where('payment_provider_object_id', $content['reference'])
                ->first();
        } elseif (Str::startsWith($content['reference'], 'DICNT')) {
            $intent = Business\Wallet\TopUpIntent::where('payment_provider', PaymentProvider::DBS_SINGAPORE)
                ->where('payment_provider_object_type', 'inward_credit_notification')
                ->where('payment_provider_object_id', $content['reference'])
                ->first();
        } elseif (Str::startsWith($content['reference'], 'DICNR')) {
            $intent = RefundIntent::where('payment_provider', PaymentProvider::DBS_SINGAPORE)
                ->where('payment_provider_object_type', 'inward_credit_notification')
                ->where('payment_provider_object_id', $content['reference'])
                ->first();
        } else {
            App::abort(404);
        }

        if (!$intent) {
            App::abort(404);
        } elseif (!( $business = $intent->business )) {
            App::abort(404);
        } elseif (!$intent instanceof Business\Wallet\TopUpIntent) {
            if (!( $charge = $intent->charge )) {
                App::abort(404);
            }
        }

        return [
            $intent,
            $business,
            $charge ?? null,
        ];
    }
}
