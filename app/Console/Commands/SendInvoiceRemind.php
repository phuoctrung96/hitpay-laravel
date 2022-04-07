<?php

namespace App\Console\Commands;

use App\Business\Invoice;
use App\Http\Resources\Business\Product as ProductResource;
use App\Notifications\SendInvoiceLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendInvoiceRemind extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:remind';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send remind to customers if an invoice is overdue';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if(Cache::has(md5($this->signature))) {
            // Skipped task as it's already running
            return;
        }

        Cache::put(md5($this->signature), true, 120);

        $invoices = Invoice::getOverdueInvoices();
        /**
         * @var Invoice $invoice
         */
        foreach ($invoices as $invoice) {
            if($invoice->isOverdue()) {
                $vars['business'] = $invoice->business;
                $vars['invoice'] = $invoice;

                $products = json_decode($invoice->products) ? json_decode($invoice->products) : null;

                $added_products = [];

                if ($products) {
                    foreach ($products as $product) {
                        $product_variation = $invoice->business->productVariations()->with('product')->find($product->variation_id);
                        array_push($added_products, ['product' => new ProductResource($product_variation->product), 'variation' => $product_variation->toArray(), 'quantity' => $product->quantity]);
                    }
                }

                $vars['products'] = $added_products;

                $pdf = PDF::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])->loadView('hitpay-email.pdf.invoice', $vars);

                $invoice->notify(new SendInvoiceLink('invoice-'.$this->invoice->reference, $pdf->output(), 'pdf'));
            }
        }
    }
}
