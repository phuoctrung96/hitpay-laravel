<?php

namespace App\Manager\Stripe;

use App\Business;
use App\Business\Charge;
use App\Enumerations\Business\PaymentMethodType;
use App\Http\Resources\Business\PaymentIntent as PaymentIntentResource;
use Illuminate\Support\Facades\URL;

class SourceAlipayManager extends SourceManager
{
    public function create(Charge $charge, Business $business) : PaymentIntentResource
    {
        $this->sourceData['return_url'] = URL::route('api.v1.business.business.plugin.charge.alipay.callback', [
            'business_id' => $business->getKey(),
            'b_charge' => $charge->getKey(),
            'method' => PaymentMethodType::ALIPAY,
        ]);

        return parent::create($charge, $business);
    }
}
