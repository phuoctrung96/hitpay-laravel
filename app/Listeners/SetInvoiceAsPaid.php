<?php

namespace App\Listeners;

use App\Business\Invoice;
use App\Business\InvoicePartialPaymentRequest;
use App\Enumerations\Business\InvoiceStatus;
use App\Events\Business\SuccessCharge;
use App\Log;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class SetInvoiceAsPaid
 * @package App\Listeners
 */
class SetInvoiceAsPaid implements ShouldQueue
{
    /**
     * @param SuccessCharge $event
     */
    public function handle(SuccessCharge $event)
    {
        if ($event->charge->invoice instanceof Invoice) {
            $event->charge->invoice->setAsPaid();
        }
        elseif ($event->charge->invoicePartialPaymentRequest instanceof InvoicePartialPaymentRequest){
                $event->charge->invoicePartialPaymentRequest->invoice->balance_amount = $event->charge->invoicePartialPaymentRequest->invoice->balance_amount - $event->charge->amount;
                $event->charge->invoicePartialPaymentRequest->invoice->save();

                if ($event->charge->invoicePartialPaymentRequest->invoice->balance_amount <= 0)
                    $event->charge->invoicePartialPaymentRequest->invoice->setAsPaid();
        }
    }
}
