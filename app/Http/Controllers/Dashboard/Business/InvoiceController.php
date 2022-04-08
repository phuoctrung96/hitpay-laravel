<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Enumerations\Business\InvoiceStatus;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\CurrencyCode;
use App\Exports\InvoiceFeedTemplate;
use App\Http\Resources\Business\Product as ProductResource;
use App\Helpers\Currency;
use App\Http\Controllers\Controller;
use App\Jobs\SendInvoiceLink;
use App\Log;
use App\Manager\BusinessManagerInterface;
use App\Manager\PaymentRequestManagerInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Business\Invoice;
use App\Business\Customer;
use App\Business\PaymentRequest;
use App\Business\TaxSetting;
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use Illuminate\Support\Collection;
use App\Enumerations\AllCountryCode;
use Illuminate\Support\Facades\Lang;
use item;

/**
 *
 * Class InvoiceController
 * @package App\Http\Controllers\Dashboard\Business
 */
class InvoiceController extends Controller
{
    /**
     * InvoiceController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, Business $business)
    {
        Gate::inspect('operate', $business)->authorize();

        $paginator = $business->invoices()->with('customer');

        $invoiceMonth = $business->invoices()->with('customer');;
        $invoicePending = $business->invoices()->with('customer');;

        $status = $request->get('status', InvoiceStatus::ALL);
        $status = strtolower($status);

        $keywords = $request->get('keywords');
        if (strlen($keywords) === 0) {
            if ($status == InvoiceStatus::ALL) {
                $paginator->whereIn('status', [
                    InvoiceStatus::PAID,
                    InvoiceStatus::PARTIALITY_PAID,
                    InvoiceStatus::SENT,
                    InvoiceStatus::PENDING,
                    InvoiceStatus::DRAFT
                ]);
            } elseif ($status == InvoiceStatus::OVERDUE) {
                $paginator->where('status', '!=', InvoiceStatus::PAID)
                ->where('due_date', '<=', now())
                ->Where('due_date', '<>', null);
            } elseif ($status == InvoiceStatus::PAID) {
                $paginator->where('status', InvoiceStatus::PAID);
            } elseif ($status == InvoiceStatus::DRAFT) {
                $paginator->where('status', InvoiceStatus::DRAFT);
            }elseif ($status == InvoiceStatus::SENT) {
                $paginator->where('status', InvoiceStatus::SENT)
                    ->where('due_date', '=', null)
                    ->orWhere('due_date', '!=', null)
                    ->where('due_date', '>', now())
                    ->where('status', InvoiceStatus::SENT)
                    ->where('business_id', $business->getKey());
            }
        }
        else{
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
            $status = null;
        }
        $paginator = $paginator->orderByDesc('id')->paginate(10);
        $paginator->appends('status', $status);

        $now = Carbon::now();
        $invoiceMonth = $invoiceMonth->where('status', '=', InvoiceStatus::PAID)
                                    ->where('currency', '=', $business->currency)
                                    ->whereMonth('invoice_date', '=', $now->month)->sum('amount');

        $invoiceMonth = getFormattedAmount($business->currency, $invoiceMonth);

        $pendingAmount = $invoicePending->whereIn('status', [InvoiceStatus::PENDING, InvoiceStatus::SENT, InvoiceStatus::OVERDUE])
                                ->where('currency', '=', $business->currency)
                                ->sum('amount');

        $pendingAmount = getFormattedAmount($business->currency, $pendingAmount);

        return Response::view('dashboard.business.invoice.index', compact('business', 'paginator', 'status','invoiceMonth', 'pendingAmount'));
    }

    public function create(Request $request, Business $business)
    {
        Gate::inspect('operate', $business)->authorize();

        $currencies = CurrencyCode::listConstants(['ZERO_DECIMAL_CURRENCIES', 'CURRENCY_SYMBOLS']);
        $zero_decimal_cur = CurrencyCode::ZERO_DECIMAL_CURRENCIES;

        $tax_settings = $business->tax_settings->toArray();
        $countries = $this->getCountries();

        return Response::view(
            'dashboard.business.invoice.create',
            compact('business', 'currencies', 'tax_settings', 'zero_decimal_cur', 'countries')
        );
    }

    public function detail(Request $request, Business $business, Invoice $invoice)
    {
        Gate::inspect('operate', $business)->authorize();
        $currencies = CurrencyCode::listConstants(['ZERO_DECIMAL_CURRENCIES', 'CURRENCY_SYMBOLS']);
        $zero_decimal_cur = CurrencyCode::ZERO_DECIMAL_CURRENCIES;

        $invoice->status = $invoice->getCustomStatus();
        $invoice->amount = str_replace(',', '', Currency::getReadableAmount($invoice->amount, $invoice->currency));
        $invoice->amount_no_tax = str_replace(',', '', Currency::getReadableAmount($invoice->amount_no_tax, $invoice->currency));

        // Get payment request of invoice
        $invoice->load('paymentRequest');
        $invoice->load('charges');

        $tax_settings = $business->tax_settings->toArray();
        $partialPayments = $invoice->invoicePartialPaymentRequests->load('paymentRequest');
        foreach($partialPayments as $item){
            $item->paymentRequest->getPayments = $item->paymentRequest->getPayments()->first();
        }

        $customer = $invoice->customer;
        return Response::view(
            'dashboard.business.invoice.detail',
            compact('business', 'currencies', 'invoice', 'customer', 'tax_settings','zero_decimal_cur', 'partialPayments',)
        );
    }

    /**
     * @param Request $request
     * @param Business $business
     * @param PaymentRequestManagerInterface $paymentRequestManager
     * @param BusinessManagerInterface $businessManager
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     * @throws \ReflectionException
     */
    public function store(
        Request $request,
        Business $business,
        PaymentRequestManagerInterface $paymentRequestManager,
        BusinessManagerInterface $businessManager
    ) {
        Gate::inspect('operate', $business)->authorize();

        $data = json_decode($request->invoice, true);

        $invoice = !empty($data['id']) ? Invoice::where('id', '=', $data['id'])->first() : new Invoice();

        $rules =  [
            'id' => [
                'nullable',
                'string',
            ],
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
                'nullable',
                Rule::in([true, false]),
            ],
            'invoice_number' => [
                'required_if:auto_invoice_number,false',
                Rule::unique('business_invoices')->ignore($invoice->invoice_number ??   '', 'invoice_number')
            ],
            'amount' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:1',
            ],
            'amount_no_tax' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:1',
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
            'allow_partial_payments' => [
                'nullable',
                Rule::in([true, false]),
            ],
            'partial_payments' => [
                'required_if:allow_partial_payments,true',
                'string'
            ],
            'attached_file' => [
                'nullable',
                'file'
            ]
        ];

        Validator::make($data, $rules)->validate([
            'customer_id.required' => 'Customer does not exist',
        ]);

        $customer = $business->customers()->find($data['customer_id']);

        $auto_number = 'INV-'.generateRandomString(4).'-'.Carbon::now()->format('Ymd');

        if ($invoice->paymentRequest instanceof PaymentRequest) {
            $invoice->paymentRequest->delete();
        }


        $invoice->business_customer_id = $customer->getKey();
        $invoice->reference = $data['reference'] ? $data['reference'] : null;
        $invoice->invoice_number = $data['auto_invoice_number'] ? $auto_number : $data['invoice_number'];
        $invoice->email = $data['email'];
        $invoice->currency = $data['currency'];
        $invoice->amount = getRealAmountForCurrency($data['currency'], $data['amount']);
        $invoice->amount_no_tax = getRealAmountForCurrency($data['currency'], $data['amount_no_tax']);
        $invoice->balance_amount = $invoice->amount;
        $invoice->products = $data['products'] ? $data['products'] : null;
        $invoice->memo = $data['memo'] ? $data['memo'] : null;
        $invoice->tax_settings_id = $data['tax_setting'] ? $data['tax_setting'] : null;
        $invoice->due_date = $data['due_date'] ? Date::createFromFormat('d/m/Y', $data['due_date'])->endOfDay() : null;
        $invoice->invoice_date = $data['invoice_date']
            ? Date::createFromFormat('d/m/Y', $data['invoice_date'])->startOfDay()
            : now()->startOfDay();
        $invoice->status = $data['status'] ? $data['status'] : InvoiceStatus::PENDING;
        $invoice->allow_partial_payments = $data['allow_partial_payments'] ?? false;

        $apiKey         = $business->apiKeys()->first();
        $businessApiKey = $apiKey->api_key;

        if ($paymentMethods = $businessManager->getBusinessProviderPaymentMethods($business, PluginProvider::INVOICE, $invoice->currency)) {
            $paymentMethods = array_flip($paymentMethods);
        } else {
            $paymentMethods = $businessManager->getByBusinessAvailablePaymentMethods($business, $invoice->currency);
        }

        /*if ($invoice->currency != CurrencyCode::SGD) {
            $paymentMethods = [
                PaymentMethodType::CARD => PaymentMethodType::CARD
            ];
        }*/

        if ($request->has('attached_file')) {
            if ($invoice->attached_file)
                Storage::delete($invoice->attached_file);

            $file = $request->file('attached_file');

            $storageDefaultDisk = Storage::getDefaultDriver();
            $destination = 'invoice-files/';

            $filename = str_replace('-', '', Str::orderedUuid()->toString()) . '.' . $file->getClientOriginalExtension();
            $path = $destination . $filename;

            $attached_file = $path;

            Storage::disk($storageDefaultDisk)->put($path, file_get_contents($file));

            $invoice->attached_file = $attached_file;
        }

        $invoice = $business->invoices()->save($invoice);

        $params = [
            'email' => $customer->email,
            'redirect_url' => route('invoice.hosted.show', [$invoice->business->id, $invoice->id]),
            'webhook' => null,
            'currency' => strtoupper($invoice->currency),
            'reference_number' => $invoice->invoice_number,
            'amount' => getReadableAmountByCurrency($invoice->currency, $invoice->amount),
            'channel' => PluginProvider::INVOICE,
            'send_email' => true,
            'purpose' => $invoice->reference,
        ];

        $paymentRequest = $paymentRequestManager->create(
            $params,
            $businessApiKey,
            array_keys($paymentMethods),
            $platform ?? null
        );

        $invoice->payment_request_id = $paymentRequest->getKey();
        $invoice->save();

        foreach($invoice->invoicePartialPaymentRequests as $request) {
            $request->delete();
        }

        if ($invoice->allow_partial_payments){
            foreach (json_decode($data['partial_payments']) as $payment){
                $params = [
                    'email' => $customer->email,
                    'redirect_url' => route('invoice.hosted.show', [$invoice->business->id, $invoice->id]),
                    'webhook' => null,
                    'currency' => strtoupper($invoice->currency),
                    'reference_number' => $invoice->invoice_number,
                    'amount' => $payment->amount,
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

                $invoice->invoicePartialPaymentRequests()->create(
                    [
                        'payment_request_id' => $partialPaymentRequest->id,
                        'amount' => $payment->amount,
                        'due_date' => isset($payment->due_date) ? Date::createFromFormat('d/m/Y', $payment->due_date)->endOfDay() : null,
                    ]
                );
            }
        }

        if ($invoice->status != InvoiceStatus::DRAFT)
            SendInvoiceLink::dispatch($business, $invoice);

        return Response::json([
            'redirect_url' => URL::route('dashboard.business.invoice.show', [
                'business_id' => $business->getKey(),
                'b_invoice' => $invoice->getKey(),
            ]),
        ]);
    }

    public function show(Request $request, Business $business, Invoice $invoice)
    {
        Gate::inspect('operate', $business)->authorize();

        return Response::view('dashboard.business.invoice.show',
            compact('business', 'invoice'));
    }

    public function edit(Request $request, Business $business, Invoice $invoice)
    {
        Gate::inspect('operate', $business)->authorize();

        if ($invoice->isPartialityPaid() || $invoice->status === InvoiceStatus::PAID)
            App::abort(403, 'You cant edit paid or partially paid invoice.');

        $currencies = CurrencyCode::listConstants(['ZERO_DECIMAL_CURRENCIES', 'CURRENCY_SYMBOLS']);
        $zero_decimal_cur = CurrencyCode::ZERO_DECIMAL_CURRENCIES;

        $invoice->amount = str_replace(',', '', Currency::getReadableAmount($invoice->amount??0, $invoice->currency));
        $invoice->amount_no_tax = str_replace(',', '', Currency::getReadableAmount($invoice->amount_no_tax??0, $invoice->currency));

        $tax_settings = $business->tax_settings->toArray();

        $partialPayments = $invoice->invoicePartialPaymentRequests;

        $customer = $invoice->customer;

        // Get all country
        $countries = $this->getCountries();

        return Response::view(
            'dashboard.business.invoice.create',
            compact('business', 'currencies', 'invoice', 'customer', 'tax_settings','zero_decimal_cur', 'partialPayments', 'countries')
        );
    }

    /**
     * @param Request $request
     * @param Business $business
     * @param Invoice $invoice
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function delete(Request $request, Business $business, Invoice $invoice)
    {
        Gate::inspect('operate', $business)->authorize();

        $invoice->delete();
        $request->session()->flash('invoice.deleted', 1);

        return Response::redirectToRoute('dashboard.business.invoice.index', [$business->getKey()]);
    }

    /**
     * @param Request $request
     * @param Business $business
     * @param Invoice $invoice
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function resend(Request $request, Business $business, Invoice $invoice)
    {
        Gate::inspect('operate', $business)->authorize();

        SendInvoiceLink::dispatch($business, $invoice);

        $request->session()->flash('invoice.resend', 1);

        return Response::redirectToRoute('dashboard.business.invoice.index', [$business->getKey()]);
    }

    /**
     * @param Request $request
     * @param Business $business
     * @param Invoice $invoice
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function remind(Request $request, Business $business, Invoice $invoice)
    {
        Gate::inspect('operate', $business)->authorize();

        SendInvoiceLink::dispatch($business, $invoice);

        $request->session()->flash('invoice.remind', 1);

        return Response::redirectToRoute('dashboard.business.invoice.index', [$business->getKey()]);
    }

    /**
     * @param Request $request
     * @param Business $business
     * @param Invoice $invoice
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function save(Request $request, Business $business, Invoice $invoice)
    {
        Gate::inspect('operate', $business)->authorize();

        $invoice->status = InvoiceStatus::PENDING;
        $invoice->save();

        SendInvoiceLink::dispatch($business, $invoice);

        return Response::redirectToRoute('dashboard.business.invoice.show', [$business->getKey(), $invoice->getKey()]);
    }

    /**
     * @param Request $request
     * @param Business $business
     * @param Invoice $invoice
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function print(Request $request, Business $business, Invoice $invoice)
    {
        Gate::inspect('operate', $business)->authorize();

        $vars['business'] = $business;
        $vars['invoice'] = $invoice;

        $pdf = PDF::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])->loadView('hitpay-email.pdf.invoice', $vars);

        return $pdf->download('invoice.pdf');
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function createInBulk(Request $request, Business $business){
        Gate::inspect('manage', $business)->authorize();
        return Response::view('dashboard.business.invoice.bulk', compact('business'));
    }

    public function downloadFeedTemplate(Request $request, Business $business){
        Gate::inspect('manage', $business)->authorize();
        $fileName = config('app.name') . "-invoice-feed.csv";
        return Excel::download(new InvoiceFeedTemplate, $fileName, \Maatwebsite\Excel\Excel::CSV)->send();
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return JsonResponse
     */
    public
    function uploadFeedFile(Request $request, Business $business)
    {
        $request->validate([
            'file' => 'required',
        ]);
        $folderName = $business->getKey() . '/invoice-feed-templates';
        $fileName = $business->getKey() . '-' . time() . '-invoice_feed_template.csv';
        $path = $request->file('file')->storeAs($folderName, $fileName);

        Artisan::queue('proceed:invoiceFeed --business_id=' . $business->getKey() . ' --file_path=' . $path);

        Session::flash('success_message', 'We  will start to upload shortly and email you the result.');
        return Response::json([
            'redirect_url' => URL::route('dashboard.business.invoice.index', $business->getKey()),
        ]);
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     */
    private function getCountries()
    {
        $countries = new Collection;

        foreach (AllCountryCode::listConstants() as $value) {
            if (Lang::has('misc.country.'.$value)) {
                $name = Lang::get('misc.country.'.$value);
            } else {
                $name = $value;
            }

            $countries->add([
                'code' => $value,
                'name' => $name,
            ]);
        }

        $data['countries'] = $countries->sortBy('name')->values()->toArray();

        return $data;
    }
}
