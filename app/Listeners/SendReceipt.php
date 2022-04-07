<?php

namespace App\Listeners;

use App\Business\PaymentRequest;
use App\Enumerations\Business\Event;
use App\Enumerations\Business\PaymentRequestStatus;
use App\Events\Business\SuccessCharge;
use App\Logics\Business\ChargeRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

/**
 * Class SendReceipt
 * @package App\Listeners
 */
class SendReceipt implements ShouldQueue
{
    /**
     * @param SuccessCharge $event
     * @throws \ReflectionException
     */
    public function handle(SuccessCharge $event)
    {
            if ($event->charge->paymentRequest instanceof PaymentRequest) {
                if (($event->charge->paymentRequest->send_email && !empty($event->charge->customer_email))) {
                    //send receipt
                    ChargeRepository::sendReceipt($event->charge, $event->charge->customer_email, true);

                    //set sent flag
                    $paymentRequest = $event->charge->paymentRequest;
                    $paymentRequest->email_status = PaymentRequestStatus::SENT;
                    DB::transaction(function () use ($paymentRequest) {
                        $paymentRequest->save();
                    });
                }
        }
    }
}
