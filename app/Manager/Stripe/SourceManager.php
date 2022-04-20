<?php

namespace App\Manager\Stripe;

use App\Actions\Business\Stripe\Charge\Source;
use App\Business;
use App\Business\Charge;
use App\Manager\PaymentIntentManagerInterface;
use App\Http\Resources\Business\PaymentIntent as PaymentIntentResource;
use Illuminate\Support\Facades\DB;

class SourceManager implements PaymentIntentManagerInterface
{
    protected $sourceData   = [];

    protected $method;

    public function __construct($method)
    {
        $this->method = $method;
    }

    public function create(Charge $charge, Business $business) : PaymentIntentResource
    {
        $data['method'] = $this->method;

        if (isset($this->sourceData['return_url'])) {
            $data['return_url'] = $this->sourceData['return_url'];
        }

        $paymentIntent = DB::transaction(function () use (
          $business,
          $charge,
          $data
        ) {
          // Save charge object because it may have some changes (like email) and if
          // we do not save this changes they will be lost
          $charge->save();

          return Source\Create::withBusiness($business)->businessCharge($charge)->data($data)->process();
        });

        return new PaymentIntentResource($paymentIntent);
    }
}
