<?php

namespace App\Listeners;

use App\Enumerations\Business\InvoiceStatus;
use Illuminate\Contracts\Queue\ShouldQueue;

class SentInvoice implements ShouldQueue
{
    /**
     * @param \App\Events\Business\SentInvoice $event
     */
    public function handle(\App\Events\Business\SentInvoice $event)
    {
        $event->invoice->status = InvoiceStatus::SENT;
        $event->invoice->save();
    }
}
