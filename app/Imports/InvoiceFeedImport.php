<?php


namespace App\Imports;

use App\Business;
use App\Business\Invoice;
use App\Business\Customer;
use App\Enumerations\Business\ImageGroup;
use App\Enumerations\Business\InvoiceStatus;
use App\Enumerations\Business\PluginProvider;
use App\Manager\BusinessManagerInterface;
use App\Manager\PaymentRequestManagerInterface;
use App\Notifications\InvoiceBulkUploadNotification;
use App\Notifications\SendInvoiceLink;
use App\Jobs\SendInvoiceLink as JobSendLink;
use HitPay\Image\Processor as ImageProcessor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class InvoiceFeedImport implements ToCollection
{
    public $business;
    public $paymentRequestManager;
    public $businessManager;
    public $errors = array();

    public function __construct(Business $business)
    {
        $this->business = $business;
        $this->paymentRequestManager = new \App\Manager\PaymentRequestManager();;
        $this->businessManager = new \App\Manager\BusinessManager();
    }

    public function collection(Collection $rows)
    {
        list($invoices, $errors) = $this->prepareInvoicesAttributes($rows);
        if (count($invoices) > 0) {
            $this->storeInvoices($invoices);
        }

        $invoicesCount = $this->countInvoices($invoices);

        if (count($errors) || $invoicesCount) {
            $business_id = $this->business->getKey();
            $feedLog = new Business\InvoiceFeedLog();
            $feedLog->business_id = $business_id;
            $feedLog->error_count = count($errors);
            $feedLog->success_count = $invoicesCount;
            $feedLog->error_msg = json_encode($errors, true);
            $feedLog->feed_date = \date('Y-m-d');
            $feedLog->save();
            $this->business->notify(new InvoiceBulkUploadNotification($feedLog));
        }
    }

    private function prepareInvoicesAttributes(Collection $rows)
    {
        $invoices = [];
        $errors = [];
        foreach ($rows as $key => $row) {
            if ($key === 0) {
                continue;
            }

            if (count($row) === 1) {
                $errors['error_format'][] = "Please use delimiter CSV file with comma (,)";
                break;
            }

            if (empty($row[0])) {
                $errors['customer_email'][] = "Customer email is required";
                continue;
            }
            if (empty($row[1])) {
                $errors['invoice_number'][] = "Invoice number is required";
                continue;
            }
            if (empty($row[2])) {
                $errors['currency'][] = "Currency is required";
                continue;
            }
            if (empty($row[3])) {
                $errors['amount'][] = "Amount is required";
                continue;
            }
            if (!is_numeric($row[3])) {
                $errors['amount'][] = "The amount need to be the numeric value";
            }

            $existCustomer = Customer::where('business_id', $this->business->getKey())->where('email', $row[0])->first();
            if (!isset($existCustomer->id)) {
                $errors[$row[0]][] = "The customer $row[0]: not exist";
            }

            $invoiceDate = now()->startOfDay();

            try {
                $invoiceDate = (isset($row[5]) && trim($row[5]) !== "") ? Date::createFromFormat('Y-m-d', $row[5])->startOfDay() : now()->startOfDay();
            } catch (\Exception $exception) {
                $errors[$row[0]][] = "The invoice date `$row[5]` wrong format, please use format YYYY-mm-dd";
            }

            $dueDate = null;

            try {
                $dueDate = (isset($row[6]) && trim($row[6]) !== "") ? Date::createFromFormat('Y-m-d', $row[6])->startOfDay() : null;
            } catch (\Exception $exception) {
                $errors[$row[0]][] = "The due date `$row[6]` wrong format, please use format YYYY-mm-dd";
            }

            if (isset($errors[$row[0]]) && count($errors[$row[0]])) {
                continue;
            }

            $invoices[$key]['business_customer_id'] = $existCustomer->id;
            $invoices[$key]['invoice_number'] = $row[1];
            $invoices[$key]['currency'] = $row[2];
            $invoices[$key]['email'] = $existCustomer->email;
            $invoices[$key]['amount'] = getRealAmountForCurrency($row[2], $row[3]);
            $invoices[$key]['reference'] = (isset($row[4]) && trim($row[4]) !== "") ? trim($row[4]) : Str::random();
            $invoices[$key]['invoice_date'] = $invoiceDate;
            $invoices[$key]['due_date'] = $dueDate;
        }

        return [$invoices, $errors];
    }

    /**
     * @param $invoices
     * @throws \Exception
     */
    public function storeInvoices($invoices)
    {
        if (count($invoices) > 0) {
            foreach ($invoices as $invoice) {
                $this->createInvoice($invoice);
            }
        }
    }

    /**
     * @param array $invoices
     * @return int
     */
    private function countInvoices(array $invoices)
    {
        $invoicesCount = 0;
        foreach ($invoices as $invoice) {
            $invoicesCount += 1;
        }

        return $invoicesCount;
    }

    private function createInvoice(array $invoiceAttributes)
    {
        try {
            $invoice = new Invoice();
            $invoice->business_id = $this->business->id;
            $invoice->business_customer_id = $invoiceAttributes['business_customer_id'];
            $invoice->currency = $invoiceAttributes['currency'];
            $invoice->invoice_number = 'INV-'.$invoiceAttributes['invoice_number'];
            $invoice->amount = $invoiceAttributes['amount'];
            $invoice->balance_amount = $invoiceAttributes['amount'] ?? 0;
            $invoice->amount_no_tax = $invoiceAttributes['amount'];
            $invoice->reference = $invoiceAttributes['reference'];
            $invoice->invoice_date = $invoiceAttributes['invoice_date'];
            $invoice->due_date = $invoiceAttributes['due_date'];
            $invoice->email = $invoiceAttributes['email'];

            $apiKey         = $this->business->apiKeys()->first();
            $businessApiKey = $apiKey->api_key;

            if ($paymentMethods = $this->businessManager->getBusinessProviderPaymentMethods($this->business, PluginProvider::INVOICE, $invoice->currency)) {
                $paymentMethods = array_flip($paymentMethods);
            } else {
                $paymentMethods = $this->businessManager->getByBusinessAvailablePaymentMethods($this->business, $invoice->currency);
            }

            $data = [
                'email' => $invoice->email,
                'redirect_url' => null,
                'webhook' => null,
                'currency' => strtoupper($invoice->currency),
                'reference_number' => null,
                'amount' => getReadableAmountByCurrency($invoice->currency, $invoice->amount),
                'channel' => PluginProvider::INVOICE,
                'send_email' => true,
                'purpose' => $invoice->reference,
            ];

            $paymentRequest = $this->paymentRequestManager->create(
                $data,
                $businessApiKey,
                array_keys($paymentMethods),
                $platform ?? null
            );

            $invoice->status = InvoiceStatus::PENDING;
            $invoice->payment_request_id = $paymentRequest->getKey();
            $invoice = $this->business->invoices()->save($invoice);

            JobSendLink::dispatch($this->business, $invoice);

        } catch (\Exception $exception) {
            Log::error($exception);
        }
    }
}
