<?php

namespace App\Jobs;

use App\Business\BusinessReferralPayout;
use App\Notifications\NotifyAdminBusinessReferralFeesExport;
use App\Notifications\NotifyAdminPartnersExport;
use App\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use SplTempFileObject;

class SendExportedBusinessReferralFeesToAdmin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $startsAt;
    private string $endsAt;
    private string $adminEmail = 'aditya@hit-pay.com';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $startsAt, string $endsAt)
    {
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $startDate = Carbon::parse($this->startsAt);
        $endDate = Carbon::parse($this->endsAt);

        $csvWriter = Writer::createFromFileObject(new SplTempFileObject());
        $csvWriter->insertOne($this->getCsvHeaders());

        $i = 1;
        DB::table('business_referral_payouts')
            ->select([
                DB::raw('b.name as business_name'),
                'business_id',
                DB::raw('rb.name as referred_business_name'),
                'referred_business_id',
                DB::raw('SUM(transaction_amount) AS transaction_amount_sum'),
                DB::raw('SUM(referral_fee) AS referral_fee_sum'),
            ])
            ->leftJoin('businesses as b', 'b.id', '=', 'business_referral_payouts.business_id')
            ->leftJoin('businesses as rb', 'rb.id', '=', 'business_referral_payouts.referred_business_id')
            ->where('paid_status', true)
            ->whereDate('business_referral_payouts.updated_at', '>=', $startDate)
            ->whereDate('business_referral_payouts.updated_at', '<=', $endDate)
            ->groupBy('business_id', 'referred_business_id')
            ->orderBy('business_id')
            ->each(function (\stdClass $row)  use (&$csvWriter, &$i) {
                $csvWriter->insertOne([
                    $i,
                    $row->business_id,
                    $row->business_name,
                    $row->referred_business_id,
                    $row->referred_business_name,
                    $row->transaction_amount_sum / 100,
                    $row->referral_fee_sum / 100,
                ]);
                $i++;
            });

        $period = $this->getPeriodAsString($startDate, $endDate);
        $csvPath = $this->getCsvPath($period);

        Storage::disk('local')->put($csvPath, $csvWriter->getContent());

        \Illuminate\Support\Facades\Notification::route('mail', $this->adminEmail)->notify(new NotifyAdminBusinessReferralFeesExport(
            $period,
            Storage::disk('local')->path($csvPath)
        ));
    }

    private function getCsvHeaders(): array
    {
        return [
            '#',
            'Business ID',
            'Business Name',
            'Referred Business ID',
            'Referred Business Name',
            'Transactions Amount',
            'Fees Amount',
        ];
    }

    private function getCsvPath(string $period): string
    {
        $path = 'email-attachments/';
        $path .= Date::today()->toDateString().'/';
        $path .= $this->adminEmail.'/';
        $path .= microtime(true);

        $path = str_replace(':', '-', $path);

        return "{$path}-business-referral-fees.csv";
    }

    private function getPeriodAsString(Carbon $startDate, Carbon $endDate)
    {
        $period = '';

        if ($startDate) {
            $period .= "from {$startDate->toDateString()} ";
        } else {
            $period .= "from last time ";
        }

        if ($endDate) {
            $period .= "until {$endDate->toDateString()}";
        } else {
            $period .= "until now";
        }

        return $period;
    }
}
