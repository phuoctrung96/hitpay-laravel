<?php

namespace App\Console\Commands;

use App\Business;
use App\Business\Charge;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\TransactionStatus;
use App\Notifications\SummarizeDailyCollection;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendBusinessDailySummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'business:daily-summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $yesterday = Carbon::yesterday();

        $businessIds = Charge::where('status', ChargeStatus::SUCCEEDED)
            ->whereDate('closed_at', $yesterday)->groupBy('business_id')->pluck('business_id');

        foreach ($businessIds as $businessId) {
            $transactions = Charge::selectRaw('currency, sum(amount) as sum')->where('business_id', $businessId)
                ->where('status', ChargeStatus::SUCCEEDED)->whereDate('closed_at', $yesterday)
                ->groupBy('currency')->pluck('sum', 'currency');

            if ($transactions->count()) {
                $business = Business::find($businessId);

                if ($business instanceof Business) {
                    $business->notify(new SummarizeDailyCollection($yesterday, $transactions->toArray()));
                }
            }
        }
    }
}
