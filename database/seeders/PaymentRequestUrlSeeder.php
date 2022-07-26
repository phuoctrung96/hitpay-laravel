<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Business;
use App\Business\PaymentRequest;

class PaymentRequestUrlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $businesses = Business::whereNotNull('slug')->get();

        foreach ($businesses as $business) {
            $paymentRequest                 = new PaymentRequest();
            $paymentRequest->url            = _env_domain('securecheckout', true) . '/payment-request/@' . $business->slug;
            $paymentRequest->amount         = 0;
            $paymentRequest->currency       = $business->currency;
            $paymentRequest->business_id    = $business->getKey();
            $paymentRequest->is_default     = true;

            $paymentRequest->save();
        }
    }
}
