<?php

namespace App\Jobs;

use App\Enumerations\Business\Wallet\Type;
use App\Models\BusinessPartner;
use App\PartnerCommission;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ChargePartnerCommissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private BusinessPartner $partner;
    private Carbon $from;
    private Carbon $to;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(BusinessPartner $partner, Carbon $from, Carbon $to)
    {
        $this->partner = $partner;
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(!empty($this->partner->last_commission_done_at) && $this->partner->last_commission_done_at->isToday()) {
            return;
        }

        $totalCommission = 0;

        $wallet = $this->partner->business->wallet(Type::AVAILABLE, 'SGD');

        foreach ($this->partner->businesses as $business) {
            $amount = $business->charges()
                ->where('status', 'succeeded')
                ->where('payment_provider_charge_method', '!=', 'cash')
                ->whereDate('closed_at', '>=', $this->from->toDateString())
                ->whereDate('closed_at', '<=', $this->to->toDateString())
                ->sum('amount');


            $commission = round($amount * $this->partner->commission / 100);

            $totalCommission += $commission;

            if($commission > 0) {
                PartnerCommission::create([
                    'business_partner_id' => $this->partner->id,
                    'business_id' => $business->id,
                    'amount' => $commission,
                    'date_from' => $this->from,
                    'date_to' => $this->to,
                ]);
            }
        }

        $wallet->incoming(
            'partner_commission',
            $totalCommission,
            'Received commission from mapped merchants from ' . $this->from->toDateString() . ' to ' . $this->to->toDateString(),
            [],
        );

        $this->partner->last_commission_done_at = now();
        $this->partner->save();
    }
}
