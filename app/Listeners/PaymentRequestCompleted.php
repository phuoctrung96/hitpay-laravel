<?php

namespace App\Listeners;

use App\Business\PaymentRequest;
use App\Enumerations\Business\PaymentRequestStatus;
use App\Events\Business\SuccessCharge;
use App\Logics\Business\ChargeRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use App\Manager\PaymentRequestManager;

/**
 * Class PaymentRequestCompleted
 * @package App\Listeners
 */
class PaymentRequestCompleted implements ShouldQueue
{
    /**
     * @param SuccessCharge $event
     * @throws \ReflectionException
     */
    public function handle(SuccessCharge $event)
    {
        $paymentRequestManager = new PaymentRequestManager();

        if ($event->charge->paymentRequest instanceof PaymentRequest) {
          if (!$event->charge->paymentRequest->allow_repeated_payments) {
            $paymentRequestManager->markAsCompleted($event->charge->paymentRequest);
          }          
        }
    }
}
