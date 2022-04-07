<?php

namespace App\Console\Commands;

use App\Business\Commission;
use App\Enumerations\PaymentProvider;
use App\Notifications\NotifyAdminAboutPendingPayout;
use ErrorException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CheckCommissionPayout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:commission-payout-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and notify admin if commission payout failed.';

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
        $commissions = Commission::with('business')
            ->whereHas('business')
            ->where('payment_provider', PaymentProvider::DBS_SINGAPORE)
            ->where('status', 'request_pending')
            ->orderByDesc('id')
            ->get();

        if ($commissions->count() === 0) {
            return;
        }

        $data = [];

        foreach ($commissions as $commission) {
            try {
                [
                    $bank_swift_code,
                    $bank_account_no,
                ] = explode('@', $commission->payment_provider_account_id);
            } catch (ErrorException $errorException) {
                Log::critical("The business ID: {$commission->business_id} doesn't have bank account set in this commission ID: {$commission->id}.");
            }

            $data[] = [
                'id' => $commission->id,
                'business_id' => $commission->business_id,
                'business_name' => $commission->business->name,
                'bank_swift_code' => $bank_swift_code ?? null,
                'bank_account_no' => $bank_account_no ?? null,
                'amount' => getFormattedAmount($commission->currency, $commission->amount),
            ];
        }

        Notification::route('slack', Config::get('services.slack.pending_payouts'))
            ->notify(new NotifyAdminAboutPendingPayout($data));
    }
}
