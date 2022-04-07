<?php

namespace App\Jobs;

use App\Business\CashbackCampaign;
use App\Business\Charge;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\PluginProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PDF;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class CheckChargeForCashback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $charge;

    /**
     * Create a new job instance.
     *
     * @param \App\Charge $charge
     */
    public function __construct(Charge $charge)
    {
        $this->charge = $charge;
    }

    /**
     * Execute the job.
     *
     * @throws \ReflectionException
     */
    public function handle()
    {
        if ($this->charge->status !== ChargeStatus::SUCCEEDED) {
            return false;
        }

        $regularCashback = $this->charge->business()->first()->getRegularCashback($this->charge)->first();

        [
            $isEligible,
            $campaignCashbackAmount,
            $campaign,
            $rule
        ] = $this->charge->isEligibleForCampaignCashback();

        if ($regularCashback){
            $this->cashbackRefund($regularCashback, $this->charge);
        }elseif ($isEligible){
            try {
                $refund = $campaign->campaignBusiness->withdrawForCampaignRefund($this->charge, $campaignCashbackAmount);

                if ($rule->balance_cashback) {
                    $rule->balance_cashback = $rule->balance_cashback - getReadableAmountByCurrency('sgd', $campaignCashbackAmount);
                    $rule->save();
                }

                $refund->is_campaign_cashback = 1;
                $refund->save();
            } catch (Exception $exception) {
                Log::error($exception->getMessage());
            }
        }
    }

    private function cashbackRefund($cashback, $charge){

        $amountToBeRefunded = $cashback->fixed_amount;
        if ($cashback->percentage)
            $amountToBeRefunded += $charge->amount * $cashback->percentage / 100;

        if ($amountToBeRefunded > $cashback->maximum_cashback)
            $amountToBeRefunded = $cashback->maximum_cashback;

        try {
            $refund = $charge->business->withdrawForRefund($charge, $amountToBeRefunded);
            $refund->is_cashback = 1;
            $refund->save();
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
        }
    }
}
