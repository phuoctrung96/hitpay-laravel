<?php

namespace App\Http\Controllers\Invoice;

use App\Business;
use App\Business\Invoice;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use PDF;
use Illuminate\Support\Facades\Response;

class InvoiceController extends Controller
{

    /**
     * @param Business $business
     * @param Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Business $business, Invoice $invoice)
    {
        $invoice->invoicePartialPaymentRequests->load('paymentRequest');

        if ($request->status === 'completed'){
            sleep(5);
            Session::flash('success_message', 'Payment was made successfully.');
        }

        $invoice->refresh();

        return Response::view('invoice.index', compact('business', 'invoice'));
    }

    /**
     * @param Business $business
     * @param Invoice $invoice
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function download(Business $business, Invoice $invoice)
    {
        $vars['business'] = $business;
        $vars['invoice'] = $invoice;

        $pdf = PDF::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])->loadView('hitpay-email.pdf.invoice', $vars);

        return $pdf->download('invoice.pdf');
    }
}
