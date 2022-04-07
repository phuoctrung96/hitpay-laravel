<?php

namespace App\Manager\GrabPay;

use App\Business;
use App\Business\Charge;
use App\Business\PaymentProvider;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use Illuminate\Support\Str;
use App\Manager\PaymentIntentManagerInterface;
use App\Http\Resources\Business\PaymentIntent as PaymentIntentResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use App\Helpers\GrabPay;
use App\Enumerations\Business\PaymentMethodType;

class PaymentIntentManager implements PaymentIntentManagerInterface
{
    protected $method;

    public function __construct($method)
    {
        $this->method = $method;
    }

    public function create(Charge $charge, Business $business) : PaymentIntentResource
    {
      $provider = $business
        ->paymentProviders
        ->where('payment_provider', PaymentProviderEnum::GRABPAY)
        ->first();

      // !!!
      // Check that provider onboarding_status is success

      if ($provider instanceof PaymentProvider) {
          $orderId = Str::uuid();

          $hidePaymentMethod = $this->method === PaymentMethodType::GRABPAY_DIRECT
            ? ['INSTALMENT', 'POSTPAID']
            : [];

          // 1. Initiate payment on GrabPay
          $res = GrabPay::chargeInitRequest('/grabpay/partner/v2/charge/init', [
            'amount' => $charge->amount,
            'currency' => strtoupper($charge->currency),
            'merchantID' => $provider->data['merchant_id'],            
            'partnerGroupTxID' => $orderId,
            'partnerTxID' => $orderId,
            'hidePaymentMethods' => $hidePaymentMethod
          ], $provider);

          // 2. Create payment intent
          $charge->home_currency                  = $charge->currency;
          $charge->home_currency_amount           = $charge->amount;
          $charge->payment_provider               = PaymentProviderEnum::GRABPAY;
          $charge->payment_provider_charge_method = $this->method;
          $business->charges()->save($charge);

          $codeVerifier = Str::random(64);
          $nonce = Str::random(32);
          // GrabPay only allows 32 chr here so we compact uuid by removing '-'
          $state = str_replace('-', '', Str::uuid());

          $paymentIntent = $charge->paymentIntents()->create([
            'business_id'                   => $charge->business_id,
            'payment_provider'              => $charge->payment_provider,
            //'payment_provider_account_id'   => $provider->payment_provider_account_id,
            'payment_provider_object_type'  => 'inward_credit_notification',
            // At this time we do not know this value and it is required
            'payment_provider_object_id'    => $orderId,
            'payment_provider_method'       => $this->method,
            'currency'                      => $charge->currency,
            'amount'                        => $charge->amount,
            'status'                        => 'pending',
            'additional_reference'          => $state,
            'data'                          => [
                'object_type'   => 'inward_credit_notification',
                'method'        => $this->method,
                'request'       => $res->request,
                'code_verifier' => $codeVerifier,
                'nonce'         => $nonce,

            ]
          ]);

          // 3. Generate redirect URL
          $code_challenge = GrabPay::urlsafe_base64encode(hash(
            'sha256',
            $codeVerifier,
            true // binary
          ));

          $query = [
            'acr_values' => 'consent_ctx:countryCode=SG,currency=' . strtoupper($charge->currency),
            'client_id' => config('services.grabpay.client_id'),
            'code_challenge' => $code_challenge,
            'code_challenge_method' => 'S256',
            'nonce' => $nonce,
            'redirect_uri' => config('services.grabpay.redirect_uri'),
            'request' => $res->request,
            'response_type' => 'code',
            'scope' => 'payment.one_time_charge',
            'state' => $state
          ];

          $data = $paymentIntent->data;
          $data['redirect_uri'] = 'https://' . config('services.grabpay.domain') . '/grabid/v1/oauth2/authorize?' . http_build_query($query);
          $paymentIntent->data = $data;

          // 4. Return result
          return new PaymentIntentResource($paymentIntent);  

      } else {
        // Provider not found
        App::abort(400, 'Can not find GrabPay provider for business: '.$business->id);
      }
    }
}
?>