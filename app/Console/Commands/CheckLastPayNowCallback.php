<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckLastPayNowCallback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:check-last-paynow-callback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check last PayNow callback and do necessity.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // If this value is not found, means the PayNow webhook isn't called for more than the time set.
        //
        if (Cache::get('last_paynow_callback') === null) {
            $lastCall = Cache::get('last_paynow_callback_datetime');

            if ($lastCall === null) {
                Log::critical('The last paynow callback was triggered more than 3 minutes ago');
            } else {
                $lastCall = Carbon::make($lastCall);

                Log::critical("The last paynow callback was triggered {$lastCall->diffForHumans()}");
            }
        }

        return 0;
    }
}
