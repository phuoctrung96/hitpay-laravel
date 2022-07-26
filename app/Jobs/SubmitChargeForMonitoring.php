<?php

namespace App\Jobs;

use App\Business;
use App\Business\Charge;
use App\Enumerations\Business\PaymentMethodType;
use App\Services\ComplyAdvantage\TransactionMonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubmitChargeForMonitoring implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $charge;

    public $business;

    public $refund;

    /**
     * Create a new job instance.
     */
    public function __construct(Charge $charge, Business $business, Business\Refund $refund = null)
    {
        $this->charge = $charge;
        $this->business = $business;
        $this->refund = $refund;
    }

    /**
     * Execute the job.
     *
     * @throws \ReflectionException
     */
    public function handle(TransactionMonitoringService $service)
    {
        if ($this->charge->payment_provider_charge_method !== PaymentMethodType::CASH) {  
            $service->submitTransaction($this->business, $this->charge, $this->refund);
        }
    }
}
