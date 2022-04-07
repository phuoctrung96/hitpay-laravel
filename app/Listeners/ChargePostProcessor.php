<?php

namespace App\Listeners;

use App\Events\Business\SuccessCharge;
use App\Services\BusinessReferralPayoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ChargePostProcessor implements ShouldQueue
{
    private BusinessReferralPayoutService $businessReferralPayoutService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(BusinessReferralPayoutService $businessReferralPayoutService)
    {
        $this->businessReferralPayoutService = $businessReferralPayoutService;
    }

    public function handle(SuccessCharge $event)
    {
        try {
            $this->businessReferralPayoutService->handle($event->charge);
        } catch (\Exception $exception) {
            Log::error($exception);
        }
    }
}
