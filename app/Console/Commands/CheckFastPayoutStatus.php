<?php

namespace App\Console\Commands;

use App\Business\Transfer;
use App\Enumerations\PaymentProvider;
use App\Notifications\NotifyAdminAboutPendingPayout;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

class CheckFastPayoutStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:dbs-fast-payment-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify in Slack about failed fast payment.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transfers = Transfer::with('business')
            ->whereHas('business')
            ->where('payment_provider', PaymentProvider::DBS_SINGAPORE)
            ->where('status', 'request_pending')
            ->orderByDesc('id')
            ->get();

        if ($transfers->count() === 0) {
            return;
        }

        $data = [];

        foreach ($transfers as $transfer) {
            [
                $bank_swift_code,
                $bank_account_no,
            ] = explode('@', $transfer->payment_provider_account_id);

            $data[] = [
                'id' => $transfer->id,
                'business_id' => $transfer->business_id,
                'business_name' => $transfer->business->name,
                'bank_swift_code' => $bank_swift_code,
                'bank_account_no' => $bank_account_no,
                'amount' => getFormattedAmount($transfer->currency, $transfer->amount),
            ];
        }

        Notification::route('slack', Config::get('services.slack.pending_payouts'))
            ->notify(new NotifyAdminAboutPendingPayout($data));
    }
}
