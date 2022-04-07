<?php

namespace App\Jobs;

use App\Business;
use App\Business\Invoice;
use App\Http\Resources\Business\Product as ProductResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Notifications\SendFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use PDF;

class SendInvoiceLink implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $business;

    public $invoice;

    /**
     * Create a new job instance.
     *
     * @param \App\Business $business
     * @param \App\Invoice $invoice
     */
    public function __construct(Business $business, Invoice $invoice)
    {
        $this->business = $business;
        $this->invoice = $invoice;
    }

    /**
     * Execute the job.
     *
     * @throws \ReflectionException
     */
    public function handle()
    {
        $vars['business'] = $this->business;
        $vars['invoice'] = $this->invoice;

        $pdf = PDF::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])->loadView('hitpay-email.pdf.invoice', $vars);

        $this->invoice->notify(new \App\Notifications\SendInvoiceLink('invoice-'.$this->invoice->reference, $pdf->output(), 'pdf'));
    }
}
