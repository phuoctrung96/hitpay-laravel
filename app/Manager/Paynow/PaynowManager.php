<?php

namespace App\Manager\Paynow;

use App\Business;
use App\Business\Charge;
use App\Manager\PaymentIntentManagerInterface;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\PaymentProvider;
use App\Http\Resources\Business\PaymentIntent as PaymentIntentResource;
use HitPay\Stripe\Charge as StripeCharge;
use HitPay\PayNow\Generator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Response\QrCodeResponse;
use Endroid\QrCode\QrCode;

class PaynowManager implements PaymentIntentManagerInterface
{
    protected $method;

    public function __construct($method)
    {
        $this->method = $method;
    }

    public function create(Charge $charge, Business $business) : PaymentIntentResource
    {
        $provider = $business
            ->paymentProviders()
            ->where('payment_provider', PaymentProvider::DBS_SINGAPORE)
            ->first()
        ;

        try {
            $generator      = Generator::new();
            $paynow         = $generator
                ->setAmount($charge->amount)
                ->setExpiryAt(Date::now()->addSeconds(500))
                ->setMerchantName($business->name)
                ->generate()
            ;

            $qrCode         = $this->getQrCode($paynow);
            $paymentIntent  = DB::transaction(function () use (
                $business,
                $provider,
                $charge,
                $qrCode,
                $generator,
                $paynow
            ) {
                $charge->home_currency                  = $charge->currency;
                $charge->home_currency_amount           = $charge->amount;
                $charge->payment_provider               = PaymentProvider::DBS_SINGAPORE;
                $charge->payment_provider_charge_method = PaymentMethodType::PAYNOW;

                $business->charges()->save($charge);

                return $charge->paymentIntents()->create([
                    'business_id'                   => $charge->business_id,
                    'payment_provider'              => $charge->payment_provider,
                    'payment_provider_account_id'   => $provider->payment_provider_account_id,
                    'payment_provider_object_type'  => 'inward_credit_notification',
                    'payment_provider_object_id'    => $generator->getReference(),
                    'payment_provider_method'       => $charge->payment_provider_charge_method,
                    'currency'                      => $charge->currency,
                    'amount'                        => $charge->amount,
                    'status'                        => 'pending',
                    'data'                          => [
                        'object_type'   => 'inward_credit_notification',
                        'method'        => PaymentMethodType::PAYNOW,
                        'data'          => $paynow //$qrCode->writeDataUri()
                    ],
                ]);
            });
        } catch (\Exception $exception) {
            throw $exception;
        }

        return new PaymentIntentResource($paymentIntent);
    }

    private function getQrCode($paynow) : QrCode
    {
        $qrCode = new QrCode($paynow);

        $qrCode->setErrorCorrectionLevel(new ErrorCorrectionLevel(ErrorCorrectionLevel::HIGH));

        $qrCode->setSize(312);
        // todo is the color correct?
        $qrCode->setBackgroundColor([
            'r' => 255,
            'g' => 255,
            'b' => 255,
        ]);
        $qrCode->setForegroundColor([
            'r' => 124,
            'g' => 26,
            'b' => 120,
        ]);

        $qrCode->setRoundBlockSize(false);
        $qrCode->setMargin(16);
        $qrCode->setLogoPath(public_path('icons/payment-methods/paynow_online.jpg'));
        $qrCode->setLogoSize(197, 125);

        return $qrCode;
    }
}
