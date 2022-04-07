<?php

namespace App\Console\Commands;

use App\Business\RecurringBilling;
use App\Enumerations\PaymentProvider;
use Illuminate\Console\Command;

class AddPaymentProviderForSubscribedRecurringPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:add-payment-provider-subscribed-recurring-plans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add payment provider to existing subscribed recurring plans.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        RecurringBilling::where('payment_provider_customer_id', 'like', 'cus_%')->update([
            'payment_provider' => PaymentProvider::STRIPE_SINGAPORE,
        ]);

        return 0;
    }
}
