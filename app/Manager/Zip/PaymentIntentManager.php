<?php

namespace App\Manager\Zip;

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
use App\Enumerations\Business\PaymentMethodType;
use App\Http\Resources\Business\PaymentIntent;
use App\Helpers\Zip;

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
        ->where('payment_provider', PaymentProviderEnum::ZIP)
        ->first();

      if ($provider instanceof PaymentProvider) {
        $requestData = [
          'shopper' => [
            'email' => $charge->customer_email,
            'billing_address' => [
              'line1' => '1 Keong Saik Road', // !!
              'city' => 'Singapore', // !!
              'state' => '', // !!
              'postal_code' => '089109', // !!
              'country' => 'SG'
            ]
          ],
          'order' => [
            'reference' => Str::uuid(),
            'amount' => floor( $charge->amount * 100 ) / 10000,
            'currency' => strtoupper($charge->currency), // SGD only
            'items' => [
            ],
            'shipping' => [
              'pickup' => true
            ]
          ],
          'config' => [
            'redirect_uri' => route('redirect.zip')
          ],
          'metadata' => [
            'merchant_id' => $provider->business_id,
            'merchant_name' => $provider->data['store_name'],
            'merchant_industry' => $provider->data['mcc']
          ]
        ];

        if ($charge->customer_name) {
          $nameArr = explode(' ', $charge->customer_name);

          $requestData['shopper']['first_name'] = $nameArr[0];

          if (count($nameArr) > 1) {
            $requestData['shopper']['last_name'] = implode(' ', array_slice($nameArr, 1));
          }          
        }

        $res = Zip::postRequest('checkouts', $requestData, 201);

        // 1. Set charge fields
        $charge->home_currency        = $charge->currency;
        $charge->home_currency_amount = $charge->amount;
        $business->charges()->save($charge);

        // Create payment intent
        $paymentIntent = $charge->paymentIntents()->create([
          'business_id'                   => $charge->business_id,
          'payment_provider'              => PaymentProviderEnum::ZIP,
          //'payment_provider_account_id'   => $provider->payment_provider_account_id,
          'payment_provider_object_type'  => 'payment_intent',
          // At this time we do not know this value and it is required
          'payment_provider_object_id'    => $res->id,
          'payment_provider_method'       => PaymentMethodType::ZIP,
          'currency'                      => $charge->currency,
          'amount'                        => $charge->amount,
          'status'                        => 'pending',
          'data'                          => [
              'redirect_uri' => $res->uri
          ],  
        ]);

        // 4. Return result
        return new PaymentIntent($paymentIntent);  
      } else {
        // Provider not found
        App::abort(400, 'Can not find Zip provider for business: '.$business->id);
      }
    }
}

?>