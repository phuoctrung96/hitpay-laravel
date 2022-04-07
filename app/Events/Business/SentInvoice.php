<?php

namespace App\Events\Business;

use App\Business\Invoice;
use Illuminate\Queue\SerializesModels;

class SentInvoice
{
    use SerializesModels;

    /**
     * The business.
     *
     * @var Invoice
     */
    public $invoice;

    /**
     * Create a new event instance.
     *
     * @param Invoice $invoice
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }
}
