<?php

namespace App\Manager\Shopee;

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
use App\Helpers\Shopee;
use App\Enumerations\Business\PaymentMethodType;
use App\Http\Resources\Business\PaymentIntent;

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
        ->where('payment_provider', PaymentProviderEnum::SHOPEE_PAY)
        ->first();

      if ($provider instanceof PaymentProvider) {
        // 1. Set charge fields
        $charge->home_currency                  = $charge->currency;
        $charge->home_currency_amount           = $charge->amount;
        $charge->payment_provider               = PaymentProviderEnum::SHOPEE_PAY;

        $business->charges()->save($charge);

        $orderId = uniqid('', true);

        // 2. Shopee
        $res = Shopee::postRequest('/v3/merchant-host/qr/create', [
          'amount' => $charge->amount,
          'currency' => strtoupper($charge->currency),
          'merchant_ext_id' => $provider->payment_provider_account_id,
          'store_ext_id' => $provider->data['sid'],
          // Shopee limits reference_id field to 25 chrs so we can not pass full uuid
          // From other side, shopee requires payment_reference_id to be unique
          'payment_reference_id' => $orderId,
          'qr_validity_period' => 1200, //300,
        ]);

        // 3. Create payment intent
        $paymentIntent = $charge->paymentIntents()->create([
          'business_id'                   => $charge->business_id,
          'payment_provider'              => $charge->payment_provider,
          //'payment_provider_account_id'   => $provider->payment_provider_account_id,
          'payment_provider_object_type'  => 'inward_credit_notification',
          // At this time we do not know this value and it is required
          'payment_provider_object_id'    => $orderId,
          'payment_provider_method'       => PaymentMethodType::SHOPEE,
          'currency'                      => $charge->currency,
          'amount'                        => $charge->amount,
          'status'                        => 'pending',
          'data'                          => [
              'object_type'   => 'inward_credit_notification',
              'qr_url'        => $res->qr_url,
              'qr_content'    => $res->qr_content    
          ],
        ]);

        // 4. Return result
        return new PaymentIntent($paymentIntent);  
      } else {
        // Provider not found
        App::abort(400, 'Can not find Shopee provider for business: '.$business->id);
      }
    }
}

?>
