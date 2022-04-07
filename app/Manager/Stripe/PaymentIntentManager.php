<?php

namespace App\Manager\Stripe;

use App\Actions\Business\Stripe\Charge\PaymentIntent\Create;
use App\Business;
use App\Business\Charge;
use App\Manager\PaymentIntentManagerInterface;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\PaymentProvider;
use App\Http\Resources\Business\PaymentIntent as PaymentIntentResource;
use HitPay\Stripe\Charge as StripeCharge;
use Illuminate\Support\Facades\DB;

class PaymentIntentManager implements PaymentIntentManagerInterface
{
    protected $method;

    public function __construct($method)
    {
        $this->method = $method;
    }

    public function create(Charge $charge, Business $business) : PaymentIntentResource
    {
        $paymentIntent = Create::withBusiness($business)->businessCharge($charge)->data([
            'method' => $this->method,
        ])->process();

        return new PaymentIntentResource($paymentIntent);
    }
}
