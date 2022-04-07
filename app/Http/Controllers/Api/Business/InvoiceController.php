<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Business\Invoice as InvoiceModel;
use App\Enumerations\Business\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Invoice;
use App\Jobs\SendInvoiceLink;
use App\Logics\Business\InvoiceRepository;
use App\Manager\BusinessManagerInterface;
use App\Manager\PaymentRequestManagerInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{

    /**
     * InvoiceController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request, BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        $invoices = InvoiceRepository::getList($request, $business);

        $invoiceMonth = InvoiceRepository::getInvoiceMonth($business);

        $pendingAmount = InvoiceRepository::getPendingAmount($business);

        $invoiceStatuses = InvoiceRepository::getAvailableStatuses();

        $resource = Invoice::collection($invoices);

        $resource->additional([
            'additional' => [
                'invoice_month' => $invoiceMonth,
                'pending_amount' => $pendingAmount,
                'statuses' => $invoiceStatuses,
            ]
        ]);

        return $resource;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business\Invoice
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function store(
        Request $request,
        BusinessModel $business,
        PaymentRequestManagerInterface $paymentRequestManager,
        BusinessManagerInterface $businessManager)
    {
        Gate::inspect('update', $business)->authorize();

        $invoice = InvoiceRepository::store($request, $business, $paymentRequestManager, $businessManager);

        return new Invoice($invoice);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Business $business
     * @param \App\Business\Invoice $invoice
     *
     * @return \App\Http\Resources\Business\Invoice
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(BusinessModel $business, InvoiceModel $invoice)
    {
        Gate::inspect('view', $business)->authorize();

        return new Invoice($invoice);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Invoice $invoice
     *
     * @return \App\Http\Resources\Business\Invoice
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function update(
        Request $request,
        BusinessModel $business,
        InvoiceModel $invoice,
        PaymentRequestManagerInterface $paymentRequestManager,
        BusinessManagerInterface $businessManager
    )
    {
        Gate::inspect('update', $business)->authorize();

        $invoice = InvoiceRepository::update($request, $business, $invoice, $paymentRequestManager, $businessManager);

        return new Invoice($invoice);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Business $business
     * @param \App\Business\Invoice $invoice
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function destroy(BusinessModel $business, InvoiceModel $invoice)
    {
        Gate::inspect('update', $business)->authorize();

        InvoiceRepository::destroy($invoice);

        return Response::json([], 204);
    }

    /**
     * Resend the specified resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Invoice $invoice
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function resend(Request $request, BusinessModel $business, InvoiceModel $invoice)
    {
        Gate::inspect('operate', $business)->authorize();

        SendInvoiceLink::dispatch($business, $invoice);

        return Response::json([
            'success' => true,
        ]);
    }
}
