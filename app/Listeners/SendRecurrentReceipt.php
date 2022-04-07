<?php

namespace App\Listeners;

use App\Business\PaymentRequest;
use App\Business\RecurringBilling;
use App\Enumerations\Business\Event;
use App\Enumerations\Business\PaymentRequestStatus;
use App\Events\Business\RecurrentChargeSucceeded;
use App\Events\Business\SuccessCharge;
use App\Logics\Business\ChargeRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class SendReceipt
 * @package App\Listeners
 */
class SendRecurrentReceipt implements ShouldQueue
{
    /**
     * @param RecurrentChargeSucceeded $event
     * @throws \ReflectionException
     */
    public function handle(RecurrentChargeSucceeded $event)
    {
        if (($recurringBilling = $event->charge->target) instanceof RecurringBilling){
            if (($recurringBilling->send_email && !empty($recurringBilling->customer_email))) {
                ChargeRepository::sendReceipt($event->charge, $event->charge->customer_email, true);
            }
        }
    }
}
