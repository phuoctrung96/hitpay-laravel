<?php

namespace App\Jobs;

use App\Business;
use App\Business\Charge;
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

    /**
     * Create a new job instance.
     */
    public function __construct(Charge $charge, Business $business)
    {
        $this->charge = $charge;
        $this->business = $business;
    }

    /**
     * Execute the job.
     *
     * @throws \ReflectionException
     */
    public function handle(TransactionMonitoringService $service)
    {
        $service->submitTransaction($this->business, $this->charge);
    }
}
