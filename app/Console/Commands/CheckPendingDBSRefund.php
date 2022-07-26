<?php

namespace App\Console\Commands;

use App\Business\Refund;
use App\Enumerations\PaymentProvider;
use App\Jobs\Wallet\Refund as RefundJob;
use Illuminate\Console\Command;

class CheckPendingDBSRefund extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:check-dbs-refund';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check DBS Refund Status';

    /**
     * Execute the console command.
     */
    public function handle() : int
    {
        Refund::where([
            'payment_provider' => PaymentProvider::DBS_SINGAPORE,
            'payment_provider_refund_type' => 'max',
            'payment_provider_refund_method' => 'wallet',
            'status' => 'pending',
        ])->each(function (Refund $refund) {
            RefundJob::dispatchNow($refund);
        });

        return 0;
    }
}
