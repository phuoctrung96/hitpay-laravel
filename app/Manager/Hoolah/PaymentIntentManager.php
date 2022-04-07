<?php

namespace App\Manager\Hoolah;

use App\Business;
use App\Business\Charge;
use App\Business\PaymentProvider;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use Illuminate\Support\Str;
use App\Manager\PaymentIntentManagerInterface;
use App\Http\Resources\Business\PaymentIntent as PaymentIntentResource;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
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
        ->where('payment_provider', PaymentProviderEnum::HOOLAH)
        ->first();

      if ($provider instanceof PaymentProvider) {
          // send request to Hoolah
          $client = new Client([
            'base_uri' => 'https://' . config('services.hoolah.domain')
          ]);

          // Login
          $res = $client->post('/auth/login', [ 
            'json' => [
              // We should use credentials stored in payment provider
              'username' => $provider->data['hoolah-username'],
              'password' => $provider->data['hoolah-password'],
            ]
          ]);

          if ($res->getStatusCode() === 200) {
            $res = json_decode((string) $res->getBody());

            // Initate order
            // https://api.hoolah.co/#operation/initiateOrder
            $orderData = [
              'consumerFirstName' => 'John',
              'consumerLastName' => 'Doe',
              'consumerEmail' => $charge->customer_email,
              'consumerPhoneNumber' => '+11111111111',
              'shippingAddress' => [
                'line1' => 'Dummy address',
                'postcode' => '111111',
                'countryCode' => 'SG'
              ],
              'billingAddress' => [
                'line1' => 'Dummy address',
                'postcode' => '111111',
                'countryCode' => 'SG'
              ],
              'items' => [
                [
                  'name' => 'HitPay',
                  'quantity' => 1,
                  'sku' => 'DummySKU',
                  'description' => 'Dummy'
                ]
              ],
              'totalAmount' => $charge->amount,
              'originalAmount' => $charge->amount,
              //
              'cartId' => $charge->id,
              'currency' => strtoupper($charge->currency),
              'returnToShopUrl' => 'https://example.com/back',
              'closeUrl' => 'https://example.com/back'
            ];

            $orderRes = $client->post('/order/initiate', [ 
              'json' => $orderData,
              'headers' => [
                'Authorization' => 'bearer ' . $res->token
              ]  
            ]);

            if ($orderRes->getStatusCode() === 201) {
              $orderRes = json_decode((string) $orderRes->getBody());
            
              // return token to FE
              // Create payment intent
              $paymentIntent = DB::transaction(function () use (
                $business,
                $provider,
                $charge,
                $orderRes
              ) {
                  $charge->home_currency                  = $charge->currency;
                  $charge->home_currency_amount           = $charge->amount;
                  $charge->payment_provider               = PaymentProviderEnum::HOOLAH;
                  $charge->payment_provider_charge_method = PaymentMethodType::HOOLAH;
  
                  $business->charges()->save($charge);
  
                  return $charge->paymentIntents()->create([
                      'business_id'                   => $charge->business_id,
                      'payment_provider'              => $charge->payment_provider,
                      //'payment_provider_account_id'   => $provider->payment_provider_account_id,
                      'payment_provider_object_type'  => 'inward_credit_notification',
                      'payment_provider_object_id'    => $orderRes->orderId,
                      'payment_provider_method'       => PaymentMethodType::HOOLAH,
                      'currency'                      => $charge->currency,
                      'amount'                        => $charge->amount,
                      'status'                        => 'pending',
                      'data'                          => [
                          'object_type'   => 'inward_credit_notification',                          
                          'method'        => PaymentMethodType::HOOLAH,
                          'orderContextToken' => $orderRes->orderContextToken,
                          'orderUuid' => $orderRes->orderUuid
                      ],
                  ]);
              });
  
              return new PaymentIntentResource($paymentIntent);
            } else {
              // Hoolah error
              throw new \Exception('/order/initiate failed with HTTP code ' . $orderRes->getStatusCode());
            }

          } else {
            // Hoolah error

          }

      } else {
        // Provider not found
        App::abort(400, 'Can not find Hoolah provider for business: '.$business->id);
      }
    }
}

?>