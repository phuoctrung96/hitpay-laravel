<?php

namespace App\Console\Commands;

use App\Business;
use App\Business\BusinessReferralPayout;
use App\Business\Charge;
use App\Enumerations\Business\Wallet\Type;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BusinessReferralFeeCalculation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'business-referral:calculate-fee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $payoutDate = Carbon::today();
        $businessPayoutFee = DB::table((new BusinessReferralPayout())->getTable())
            ->select('business_id', DB::raw('SUM(referral_fee) as daily_fee'))
            ->where('paid_status', false)
            ->where('currency', 'sgd')
            ->groupBy('business_id')
            ->orderBy('business_id');

        $businessPayoutFee->each(function (\stdClass $record) use ($payoutDate) {
            DB::transaction(function() use ($record, $payoutDate) {
                /** @var Business $business */
                if($business = Business::find($record->business_id)) {
                    $wallet = $business->wallet(Type::AVAILABLE, 'SGD');
                    $wallet->incoming(
                        'business_referral_commission',
                        $record->daily_fee,
                        'Received referral fee from mapped merchants on ' . $payoutDate->toDateString(),
                        [],
                    );

                    BusinessReferralPayout::query()
                        ->where('business_id', $business->id)
                        ->where('paid_status', false)
                        ->where('currency', 'sgd')
                        ->update(['paid_status' => true]);
                }
            });
        });
    }
}
