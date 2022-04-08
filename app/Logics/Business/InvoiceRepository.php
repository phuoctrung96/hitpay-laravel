<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\Invoice;
use App\Business\PaymentRequest;
use App\Enumerations\Business\InvoiceStatus;
use App\Enumerations\Business\PaymentRequestStatus;
use App\Enumerations\Business\PluginProvider;
use App\Helpers\Pagination;
use App\Jobs\SendInvoiceLink;
use App\Manager\BusinessManagerInterface;
use App\Manager\PaymentRequestManagerInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoiceRepository
{
    public static function getList(Request $request, Business $business)
    {
        $paginator = $business->invoices()->with('customer');

        $filterStatus = $request->get('status', InvoiceStatus::ALL);

        $filterStatus = strtolower($filterStatus);

        $keywords = $request->get('keywords', null);

        if (strlen($keywords) !== 0) {
            $paginator->select($paginator->qualifyColumn('*'));

            $paginator->whereIn($paginator->qualifyColumn('status'), [
                InvoiceStatus::PAID,
                InvoiceStatus::SENT,
                InvoiceStatus::PENDING
            ])->where(function (Builder $query) use ($keywords) {
                $query->orWhere($query->qualifyColumn('invoice_number'), 'like', '%'.$keywords.'%');
                $query->orWhere($query->qualifyColumn('reference'), 'like', '%'.$keywords.'%');
                $query->orWhereHas('customer', function($query) use ($keywords) {
                    $query->where('name', 'like', '%' . $keywords . '%');
                });
            });

            $filterStatus = null;
        }

        if ($filterStatus == InvoiceStatus::ALL) {
            $paginator->whereIn('status', [
                InvoiceStatus::PAID,
                InvoiceStatus::SENT,
                InvoiceStatus::PENDING,
                InvoiceStatus::DRAFT,
            ]);
        } elseif ($filterStatus == InvoiceStatus::OVERDUE) {
            $paginator->where('status', '!=', InvoiceStatus::PAID)
                ->where('due_date', '<=', now())
                ->where('due_date', '<>', null);
        } elseif ($filterStatus == InvoiceStatus::PAID) {
            $paginator->where('status', InvoiceStatus::PAID);
        } elseif ($filterStatus == InvoiceStatus::DRAFT) {
            $paginator->where('status', InvoiceStatus::DRAFT);
        } elseif ($filterStatus == InvoiceStatus::SENT) {
            $paginator->where('status', InvoiceStatus::SENT)
                ->where('due_date', '=', null)
                ->orWhere('due_date', '!=', null)
                ->where('due_date', '>', now())
                ->where('status', InvoiceStatus::SENT);
        } elseif ($filterStatus == InvoiceStatus::PARTIALITY_PAID) {
            $paginator->where('allow_partial_payments', Invoice::ENABLE_PARTIAL_PAYMENT)
                ->whereHas('invoicePartialPaymentRequests.paymentRequest', function($query) {
                    $query->where('status', PaymentRequestStatus::COMPLETED);
                });
        }

        $paginateNumber = Pagination::getDefaultPerPage();

        if ($request->has('per_page')) {
            $paginateNumber = $request->per_page;
        }

        $paginator = $paginator->orderByDesc('created_at')->paginate($paginateNumber);

        return $paginator;
    }

    public static function getInvoiceMonth(Business $business)
    {
        $invoiceMonth = $business->invoices()->with('customer');

        $now = Carbon::now();

        $invoiceMonth = $invoiceMonth->where('status', '=', InvoiceStatus::PAID)
            ->where('currency', '=', $business->currency)
            ->whereMonth('invoice_date', '=', $now->month)->sum('amount');

        return getFormattedAmount($business->currency, $invoiceMonth);
    }


    public static function getPendingAmount(Business $business)
    {
        $invoicePending = $business->invoices()->with('customer');

        $totalAmountPending = $invoicePending->whereIn('status', [InvoiceStatus::PENDING, InvoiceStatus::SENT, InvoiceStatus::OVERDUE])
            ->where('currency', '=', $business->currency)
            ->sum('amount');

        return getFormattedAmount($business->currency, $totalAmountPending);
    }

    public static function getAvailableStatuses()
    {
        return [
            [
                'id' => InvoiceStatus::ALL,
                'name' => "All",
            ],
            [
                'id' => InvoiceStatus::SENT,
                'name' => "Sent",
            ],
            [
                'id' => InvoiceStatus::OVERDUE,
                'name' => "Overdue",
            ],
            [
                'id' => InvoiceStatus::PAID,
                'name' => "Paid",
            ],
            [
                'id' => InvoiceStatus::PARTIALITY_PAID,
                'name' => "Partiality Paid",
            ],
        ];
    }

    /**
     * Create a new invoice.
     *
     * NOTE: Without products and tax setting.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Business\Invoice
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function store(
        Request $request,
        Business $business,
        PaymentRequestManagerInterface $paymentRequestManager,
        BusinessManagerInterface $businessManager) : Invoice
    {
        $rules = self::getRule($business);

        $requestData = Validator::make($request->all(), $rules)->validate();

        $invoiceNumber = $requestData['invoice_number'];

        if ($requestData['auto_invoice_number']) {
            $invoiceNumber = 'INV-'.generateRandomString(4).'-'.Carbon::now()->format('Ymd');
        }

        // check duplicate invoiceNumber
        $availableInvoice = $business->invoices()->where('invoice_number', $invoiceNumber)->first();

        if ($availableInvoice) {
            $validator = Validator::make([], []);

            $message = "Duplicate invoice number";

            if ($requestData['auto_invoice_number']) {
                $message = "Duplicate invoice number, please resubmit";
            }

            $validator->errors()->add('invoice_number', $message);

            throw new ValidationException($validator);
        }

        // validate payment partial amount not same with total amount
        if ($requestData['allow_partial_payments']) {
            $totalAmount = $requestData['amount'];
            $totalPartialAmount = 0;
            foreach ($requestData['partial_payments'] as $partialPayment) {
                $totalPartialAmount = $totalPartialAmount + $partialPayment['amount'];
            }

            if ($totalAmount != $totalPartialAmount) {
                throw ValidationException::withMessages([
                    'amount' => 'Total partial amount not same with total amount',
                ]);
            }
        }

        $customer = $business->customers()->findOrFail($requestData['customer_id']);

        if ($requestData['email'] == "") {
            $requestData['email'] = $customer->email;
        }

        $invoiceData['business_customer_id'] = $customer->getKey();
        $invoiceData['reference'] = $requestData['reference'] ?? null;
        $invoiceData['invoice_number'] = $invoiceNumber;
        $invoiceData['email'] = $requestData['email'] ?? null;
        $invoiceData['currency'] = $requestData['currency'] ?? null;
        $invoiceData['amount'] = getRealAmountForCurrency($requestData['currency'], $requestData['amount']);
        $invoiceData['amount_no_tax'] = getRealAmountForCurrency($requestData['currency'], $requestData['amount_no_tax']);
        $invoiceData['memo'] = $requestData['memo'] ?? null;
        $invoiceData['due_date'] = $requestData['due_date'] ? Date::createFromFormat('d/m/Y', $requestData['due_date'])->endOfDay() : null;
        $invoiceData['invoice_date'] = $requestData['invoice_date'] ? Date::createFromFormat('d/m/Y', $requestData['invoice_date'])->startOfDay() : now()->startOfDay();
        $invoiceData['status'] = $requestData['status'] ?? InvoiceStatus::PENDING;
        $invoiceData['balance_amount'] = $invoiceData['amount'];
        $invoiceData['products'] = $requestData['products'] ?? null;
        $invoiceData['allow_partial_payments'] = $requestData['allow_partial_payments'] ? Invoice::ENABLE_PARTIAL_PAYMENT : Invoice::DISABLE_PARTIAL_PAYMENT;
        $invoiceData['tax_settings_id'] = $requestData['tax_setting'] ?? null;

        $apiKey         = $business->apiKeys()->first();
        $businessApiKey = $apiKey->api_key;

        if ($paymentMethods = $businessManager->getBusinessProviderPaymentMethods($business, PluginProvider::INVOICE, $invoiceData['currency'])) {
            $paymentMethods = array_flip($paymentMethods);
        } else {
            $paymentMethods = $businessManager->getByBusinessAvailablePaymentMethods($business, $invoiceData['currency']);
        }

        $paymentRequestData = [
            'email' => $requestData['email'],
            'redirect_url' => null,
            'webhook' => null,
            'currency' => strtoupper($invoiceData['currency']),
            'reference_number' => $invoiceData['invoice_number'],
            'amount' => getReadableAmountByCurrency($invoiceData['currency'], $invoiceData['amount']),
            'channel' => PluginProvider::INVOICE,
            'send_email' => true,
            'purpose' => $invoiceData['reference'],
        ];

        try {
            DB::beginTransaction();

            // create payment request
            $paymentRequest = $paymentRequestManager->create(
                $paymentRequestData,
                $businessApiKey,
                array_keys($paymentMethods),
                $platform ?? null
            );

            $invoiceData['payment_request_id'] = $paymentRequest->getKey();

            // create attachment
            if ($request->has('attached_file')) {
                $file = $request->file('attached_file');

                $storageDefaultDisk = Storage::getDefaultDriver();

                $destination = 'invoice-files/';

                $filename = str_replace('-', '', Str::orderedUuid()->toString()) . '.' . $file->getClientOriginalExtension();

                $path = $destination . $filename;

                $attachedFile = $path;

                Storage::disk($storageDefaultDisk)->put($path, file_get_contents($file));

                $invoiceData['attached_file'] = $attachedFile;
            }

            // save invoice
            $invoice = $business->invoices()->create($invoiceData);

            // check partial payments
            if ($requestData['allow_partial_payments']) {
                foreach ($requestData['partial_payments'] as $partialPayment) {
                    $params = [
                        'email' => $requestData['email'],
                        'redirect_url' => null,
                        'webhook' => null,
                        'currency' => strtoupper($invoice->currency),
                        'reference_number' => $invoice->invoice_number,
                        'amount' => $partialPayment['amount'],
                        'channel' => PluginProvider::INVOICE,
                        'send_email' => true,
                        'purpose' => $invoice->reference,
                    ];

                    $partialPaymentRequest = $paymentRequestManager->create(
                        $params,
                        $businessApiKey,
                        array_keys($paymentMethods),
                        $platform ?? null
                    );

                    $invoice->invoicePartialPaymentRequests()->create([
                        'payment_request_id' => $partialPaymentRequest->id,
                        'amount' => $partialPayment['amount'],
                        'due_date' => isset($partialPayment['due_date']) ? Date::createFromFormat('d/m/Y', $partialPayment['due_date'])->endOfDay() : null,
                    ]);
                }
            }

            if ($invoice->status != InvoiceStatus::DRAFT) {
                SendInvoiceLink::dispatch($business, $invoice);
            }

            DB::commit();

            return $invoice;
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Update an existing invoice.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business\Invoice $invoice
     *
     * @return \App\Business\Invoice
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function update(
        Request $request,
        Business $business,
        Invoice $invoice,
        PaymentRequestManagerInterface $paymentRequestManager,
        BusinessManagerInterface $businessManager
    ) : Invoice
    {
        $rules = self::getRule($business);

        $requestData = Validator::make($request->all(), $rules)->validate();

        if ($requestData['email'] != "") {
            $invoice->email = $requestData['email'];
        }

        $invoiceNumber = $requestData['invoice_number'];

        if ($requestData['auto_invoice_number']) {
            $invoiceNumber = 'INV-'.generateRandomString(4).'-'.Carbon::now()->format('Ymd');
        }

        if ($invoice->status == InvoiceStatus::PAID || $invoice->status == InvoiceStatus::PARTIALITY_PAID) {
            $validator = Validator::make([], []);

            $message = "Status invoice was " . $invoice->status . ". You cant edit it";

            $validator->errors()->add('status', $message);

            throw new ValidationException($validator);
        }

        $invoice->business_customer_id = $requestData['customer_id'];
        $invoice->reference = $request->reference ?? null;
        $invoice->invoice_number = $invoiceNumber;
        $invoice->currency = $request->currency ?? null;
        $invoice->amount = getRealAmountForCurrency($requestData['currency'], $requestData['amount']);
        $invoice->amount_no_tax = getRealAmountForCurrency($requestData['currency'], $requestData['amount_no_tax']);
        $invoice->memo = $request->memo ?? null;
        $invoice->due_date = $request->due_date ? Date::createFromFormat('d/m/Y', $requestData['due_date'])->endOfDay() : $invoice->due_date;
        $invoice->invoice_date = $request->invoice_date ? Date::createFromFormat('d/m/Y', $requestData['invoice_date'])->startOfDay() : $invoice->invoice_date;
        $invoice->allow_partial_payments = $requestData['allow_partial_payments'] ? Invoice::ENABLE_PARTIAL_PAYMENT : Invoice::DISABLE_PARTIAL_PAYMENT;
        $invoice->tax_settings_id = $requestData['tax_setting'] ?? $invoice->tax_settings_id;
        $invoice->products = $requestData['products'] ?? $invoice->products;

        if ($invoice->paymentRequest instanceof PaymentRequest) {
            $invoice->paymentRequest->delete();
        }

        $apiKey         = $business->apiKeys()->first();
        $businessApiKey = $apiKey->api_key;

        if ($paymentMethods = $businessManager->getBusinessProviderPaymentMethods($business, PluginProvider::INVOICE, $invoice->currency)) {
            $paymentMethods = array_flip($paymentMethods);
        } else {
            $paymentMethods = $businessManager->getByBusinessAvailablePaymentMethods($business, $invoice->currency);
        }

        $paymentRequestData = [
            'email' => $invoice->email,
            'redirect_url' => null,
            'webhook' => null,
            'currency' => strtoupper($invoice->currency),
            'reference_number' => $invoice->invoice_number,
            'amount' => getReadableAmountByCurrency($invoice->currency, $invoice->amount),
            'channel' => PluginProvider::INVOICE,
            'send_email' => true,
            'purpose' => $invoice->reference,
        ];

        try {
            DB::beginTransaction();

            $paymentRequest = $paymentRequestManager->create(
                $paymentRequestData,
                $businessApiKey,
                array_keys($paymentMethods),
                $platform ?? null
            );

            $invoice->payment_request_id = $paymentRequest->getKey();

            // create attachment
            if ($request->has('attached_file')) {
                if ($invoice->attached_file) {
                    Storage::delete($invoice->attached_file);
                }

                $file = $request->file('attached_file');

                $storageDefaultDisk = Storage::getDefaultDriver();

                $destination = 'invoice-files/';

                $filename = str_replace('-', '', Str::orderedUuid()->toString()) . '.' . $file->getClientOriginalExtension();

                $path = $destination . $filename;

                $attachedFile = $path;

                Storage::disk($storageDefaultDisk)->put($path, file_get_contents($file));

                $invoice->attached_file = $attachedFile;
            }

            $invoice->save();

            // check partial payments
            if ($requestData['allow_partial_payments']) {
                self::deletePartialPayment($invoice);

                foreach ($requestData['partial_payments'] as $partialPayment) {
                    $params = [
                        'email' => $invoice->email,
                        'redirect_url' => null,
                        'webhook' => null,
                        'currency' => strtoupper($invoice->currency),
                        'reference_number' => $invoice->invoice_number,
                        'amount' => $partialPayment['amount'],
                        'channel' => PluginProvider::INVOICE,
                        'send_email' => true,
                        'purpose' => $invoice->reference,
                    ];

                    $partialPaymentRequest = $paymentRequestManager->create(
                        $params,
                        $businessApiKey,
                        array_keys($paymentMethods),
                        $platform ?? null
                    );

                    $invoice->invoicePartialPaymentRequests()->create([
                        'payment_request_id' => $partialPaymentRequest->id,
                        'amount' => $partialPayment['amount'],
                        'due_date' => isset($partialPayment['due_date']) ? Date::createFromFormat('d/m/Y', $partialPayment['due_date'])->endOfDay() : null,
                    ]);
                }
            } else {
                self::deletePartialPayment($invoice);
            }

            DB::commit();

            return $invoice->refresh();
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    private static function deletePartialPayment($invoice)
    {
        // delete partials payment request if any
        foreach($invoice->invoicePartialPaymentRequests as $partialPaymentRequest) {
            // delete payment request first
            $paymentRequestId = $partialPaymentRequest->payment_request_id;

            $paymentRequest = PaymentRequest::find($paymentRequestId);

            if ($paymentRequest) {
                $paymentRequest->delete();
            }

            // payment request done delete, then delete partial payment request
            $partialPaymentRequest->delete();
        }
    }

    public static function destroy(Invoice $invoice)
    {
        // check is allow partial payment
        if ($invoice->allow_partial_payments) {
            self::deletePartialPayment($invoice);
        }

        $invoice->delete();
    }

    private static function getRule(Business $business)
    {
        return [
            'customer_id' => [
                'required',
                Rule::exists('business_customers', 'id')->where('business_id', $business->id),
            ],
            'reference' => [
                'nullable',
                'string',
            ],
            'due_date' => [
                'nullable',
                'date_format:d/m/Y',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'auto_invoice_number' => [
                'required',
                Rule::in([0, 1]),
            ],
            'invoice_number' => [
                'required_if:auto_invoice_number,0'
            ],
            'amount' => [
                'required',
                'numeric',
                'between:0.01,' . \App\Helpers\Invoice::MAX_AMOUNT
            ],
            'amount_no_tax' => [
                'required',
                'numeric',
                'between:0.01,' . \App\Helpers\Invoice::MAX_AMOUNT
            ],
            'products' => [
                'nullable',
                'string',
            ],
            'memo' => [
                'nullable',
                'string',
            ],
            'currency' => [
                'required',
                'string',
                'min:3',
            ],
            'tax_setting' => [
                'nullable',
                'string',
            ],
            'invoice_date' => [
                'nullable',
                'date_format:d/m/Y',
            ],
            'status' => [
                'nullable',
            ],
            'attached_file' => [
                'nullable',
                'file'
            ],
            'allow_partial_payments' => [
                'required',
                Rule::in([0, 1]),
            ],
            'partial_payments.*' => [
                'required_if:allow_partial_payments,true'
            ],
            'partial_payments.*.amount' => [
                'required_if:allow_partial_payments,true',
                'numeric',
                'between:0.01,' . \App\Helpers\Invoice::MAX_AMOUNT
            ],
            'partial_payments.*.due_date' => [
                'nullable',
                'date_format:d/m/Y',
            ],
        ];
    }
}
