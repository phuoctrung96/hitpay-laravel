<?php

namespace App\Services;


use App\Business;
use App\Business\Charge;
use App\Business\Xero;
use App\Enumerations\Business\ChargeStatus;
use App\Http\Resources\Business\Order;
use Carbon\Carbon;
use DateTime;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use XeroAPI\XeroPHP\ApiException;
use XeroAPI\XeroPHP\Configuration;
use XeroAPI\XeroPHP\Models\Accounting\Account;
use XeroAPI\XeroPHP\Models\Accounting\BankTransaction;
use XeroAPI\XeroPHP\Models\Accounting\BankTransactions;
use XeroAPI\XeroPHP\Models\Accounting\Contact;
use XeroAPI\XeroPHP\Models\Accounting\Contacts;
use XeroAPI\XeroPHP\Models\Accounting\Invoice;
use XeroAPI\XeroPHP\Models\Accounting\Invoices;
use XeroAPI\XeroPHP\Models\Accounting\LineItem;
use XeroAPI\XeroPHP\Models\Accounting\Payment;
use XeroAPI\XeroPHP\Api\AccountingApi;

class XeroSalesService
{
    /**
     * @var Business
     */
    private $business;
    /**
     * @var Account
     */
    private $currentFeeAccount;
    /**
     * @var Account
     */
    private $currentAccount;

    public function __construct(Business $business)
    {

        $this->business = $business;
    }

    /**
     * @return bool
     */
    public function shouldSync(): bool
    {
        return $this->business->canSyncWithXero()
            && !empty($this->business->xero_sync_date)
            && $this->business->xero_sync_date <= date('Y-m-d');
    }

    /**
     * @param string $status
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     * @throws \ReflectionException
     */
    public function sync(string $status)
    {
        $lastSyncDate = $this->business->getLastXeroSyncDate();
        $dateIsPast = $lastSyncDate < date('Y-m-d');
        $startDate = $dateIsPast ? $lastSyncDate : date('Y-m-d', strtotime('-1 days'));

        $this->syncInvoices($status, $startDate, $dateIsPast);

        $this->business->xero_last_sync_date = now();
        $this->business->save();
    }



    public function markInvoiceAsPaid(string $invoiceId, $fees, $paymentDate)
    {
        try {
            $accountingApi = $this->makeAccountingApi();
            /** @var \XeroAPI\XeroPHP\Models\Accounting\Invoices $invoice */
            if($invoices = $accountingApi->getInvoice($this->business->xero_tenant_id, $invoiceId)) {
                $invoice = $invoices->getInvoices()[0];

                if($invoice->getStatus() != 'PAID') {
                    $account = $accountingApi->getAccount($this->business->xero_tenant_id, $this->business->xero_bank_account_id);
                    $bankAccount = $account->getAccounts()[0];

                    $payment = new Payment();
                    $payment->setAccount($bankAccount);
                    $payment->setInvoice($invoice);
                    $payment->setAmount($invoice->getAmountDue());
                    $payment->setCode($bankAccount->getCode());
                    $payment->setStatus(Xero::PAYMENT_STATUS);
                    $payment->setDate($paymentDate);
                    $payment->setIsReconciled(true);
                    $accountingApi->createPayment($this->business->xero_tenant_id, $payment);

                    $feeAccount = $accountingApi->getAccount($this->business->xero_tenant_id, $this->business->xero_payment_fee_account_id)
                        ->getAccounts()[0];

                    $spendMoneyTransaction = new BankTransaction();

                    $lineItem = new LineItem();
                    $lineItem->setDescription('Payment fees');
                    $lineItem->setAccountCode($feeAccount->getCode());
                    $lineItem->setUnitAmount($fees);

                    $spendMoneyTransaction
                        ->setDate($paymentDate)
                        ->setType(BankTransaction::TYPE_SPEND)
                        ->setIsReconciled(true)
                        ->setBankAccount($bankAccount)
                        ->setContact($this->getCurrentContact($accountingApi))
                        ->setLineItems([$lineItem]);

                    $transactions = new BankTransactions();
                    $transactions->setBankTransactions([$spendMoneyTransaction]);

                    $response = $accountingApi->createBankTransactions($this->business->xero_tenant_id, $transactions);

                    return true;
                }
            }
        } catch (ApiException $exception) {
            Log::channel('xero')->error($exception);
        } catch (\Exception $exception) {
            Log::error($exception);
        }

        return false;
    }

    /**
     * @return AccountingApi
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    private function makeAccountingApi()
    {
        return XeroApiFactory::makeAccountingApi($this->business);
    }

    public function getCurrentContact(): Contact
    {
        $accountingApi = $this->makeAccountingApi();

        if (!empty($this->business->xero_contact_id)) {
            $currentContact = $accountingApi->getContact($this->business->xero_tenant_id, $this->business->xero_contact_id);
            return $currentContact->getContacts()[0];
        }

        $currentContact = $this->getOrCreateXeroContact();
        $this->business->xero_contact_id = $currentContact->getContactId();
        $this->business->update();

        return $currentContact;
    }

    private function getOrCreateXeroContact(): Contact
    {
        $accountingApi = $this->makeAccountingApi();

        /** @var Contacts $contacts */
        $contacts = $accountingApi->getContacts($this->business->xero_tenant_id);
        foreach ($contacts->getContacts() as $contact) {
            if(strpos($contact->getName(), config('app.name') . ' - ' . $this->business->getName()) !== false) {
                return $contact;
            }
        }

        $contact = new Contact();
        $contact->setName(config('app.name') . ' - ' . $this->business->getName());
        $contacts = new Contacts();
        $contacts->setContacts([$contact]);
        $apiResponse = $accountingApi->createContacts($this->business->xero_tenant_id, $contacts);

        return $apiResponse->getContacts()[0];
    }

    /**
     * @param AccountingApi $accountingApi
     * @param string $status
     * @return array
     */
    private function getAccounts(AccountingApi $accountingApi, string $status)
    {
        $currentAccount = $currentFeeAccount = null;
        if ($status === 'success' && isset($this->business->xero_account_id)) {
            $currentAccount = $accountingApi->getAccount($this->business->xero_tenant_id, $this->business->xero_account_id);
            $currentAccount = $currentAccount->getAccounts()[0];
        }
        if ($status === 'refund' && isset($this->business->xero_refund_account_id)) {
            $currentAccount = $accountingApi->getAccount($this->business->xero_tenant_id, $this->business->xero_refund_account_id);
            $currentAccount = $currentAccount->getAccounts()[0];
        }
        if ($status === 'success' && isset($this->business->xero_fee_account_id)) {
            $currentFeeAccount = $accountingApi->getAccount($this->business->xero_tenant_id, $this->business->xero_fee_account_id);
            $currentFeeAccount = $currentFeeAccount->getAccounts()[0];
        }

        return [$currentAccount, $currentFeeAccount];
    }

    /**
     * @param AccountingApi $accountingApi
     * @param string $name
     * @param string $type
     * @return mixed
     */
    private function createAccount(
        AccountingApi $accountingApi,
        string $name,
        string $type
    )
    {
        $newAccount = new Account();
        $newAccount->setCode(rand(10000, 99999));
        $newAccount->setType($type);
        $newAccount->setName($name);
        $newAccount->setEnablePaymentsToAccount(true);
        $result = $accountingApi->createAccount($this->business->xero_tenant_id, $newAccount);
        return $result->getAccounts()[0];
    }

    /**
     * @param AccountingApi $accountingApi
     */
    private function makeAccounts(AccountingApi $accountingApi)
    {
        $shouldUpdateBusiness = false;
        if(empty($this->business->xero_account_id)) {
            $this->business->xero_account_id = $this->createAccount(
                $accountingApi,
                config('app.name') . '-' . $this->business->getName() . ' - Sales - ' . now()->toDateTimeString(),
                $this->business->xero_sales_account_type
            )->getAccountId();
            $shouldUpdateBusiness = true;
        }
        if(empty($this->business->xero_refund_account_id)) {
            $this->business->xero_refund_account_id = $this->createAccount(
                $accountingApi,
                config('app.name') . '-' . $this->business->getName() . ' - Refund - ' . now()->toDateTimeString(),
                $this->business->xero_refund_account_type
            )->getAccountId();
            $shouldUpdateBusiness = true;
        }
        if(empty($this->business->xero_fee_account_id)) {
            $this->business->xero_fee_account_id = $this->createAccount(
                $accountingApi,
                config('app.name') . '-' . $this->business->getName() . ' - Fee - ' . now()->toDateTimeString(),
                $this->business->xero_fee_account_type
            )->getAccountId();
            $shouldUpdateBusiness = true;
        }

        if ($shouldUpdateBusiness) {
            $this->business->update();
        }
    }

    /**
     * @param string $status
     * @param string $date
     * @param bool $dateIsPast
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     * @throws \ReflectionException
     */
    private function syncInvoices(string $status, string $date, bool $dateIsPast)
    {
        $accountingApi = $this->makeAccountingApi();
        $currentContact = $this->getCurrentContact($accountingApi);
        $this->makeAccounts($accountingApi);
        list($this->currentAccount, $this->currentFeeAccount) = $this->getAccounts($accountingApi, $status);

        $orderSales = $this->getOrders($status, $date, $dateIsPast);

        if (count($orderSales) > 0) {
            list($feeLineItems, $lineItems) = $this->getLineItemsFromOrders($orderSales, $status, $date, $dateIsPast);

            $invoiceData = $this->getInvoicesData($status, $feeLineItems, $lineItems, $currentContact, $date);

            if (count($invoiceData) > 0) {
                $createdInvoices = $this->createInvoices($accountingApi, $invoiceData);

                $this->createPayments($accountingApi, $createdInvoices, $this->currentAccount, $this->currentFeeAccount, $status);
                $this->markOrdersAsImported($orderSales);
                $this->updateBusinessLog($status, $feeLineItems, $lineItems);
            }
        }
    }

    /**
     * @param string $status
     * @param string $date
     * @param bool $dateIsPast
     * @return Charge[]
     */
    private function getOrders(string $status, string $date, bool $dateIsPast)
    {
        $orderSales = $this->business->charges()->with('target')
            ->where('currency', 'sgd')
            ->where('xero_imported', false);
        if ($status === 'success') {
            $orderSales = $orderSales->where('status', ChargeStatus::SUCCEEDED);
        } else if ($status === 'refund') {
            $orderSales = $orderSales->where('status', ChargeStatus::REFUNDED);
        }
        if ($dateIsPast) {
            $orderSales = $orderSales->whereBetween('closed_at', [$date, date('Y-m-d')]);
        } else {
            $orderSales = $orderSales->whereDate('closed_at', '=', date('Y-m-d', strtotime('-1 day')));
        }

        $orders = [];
        foreach($orderSales->get() as $orderSale) { if(!$orderSale->isForXeroInvoice()) { $orders[] = $orderSale; } }

        return $orders;
    }

    /**
     * @param Charge $orderSale
     * @param string $status
     * @return string
     */
    private function getOrderSaleDescription(Charge $orderSale, string $status)
    {
        if (in_array($orderSale->payment_provider_charge_method, Charge::stripe_methods)) {
            $description = config('app.name') . '-' . $this->business->getName() . ' - Stripe Payment Method -(' . $orderSale->id . ')' . ($status === 'success' ? 'PAID' : 'REFUNDED');
        } elseif($orderSale->payment_provider_charge_method == 'paynow_online') {
            $description = config('app.name') . '-' . $this->business->getName() . ' - PayNow Online Payment Method -(' . $orderSale->id . ')' . ($status === 'success' ? 'PAID' : 'REFUNDED');
        } else {
            $description = config('app.name') . '-' . $this->business->getName() . ' - Cash Payment Method -(' . $orderSale->id . ')' . ($status === 'success' ? 'PAID' : 'REFUNDED');
        }

        $description .= $this->getOrderDescription($orderSale);

        return $description;
    }

    /**
     * @param Charge $orderSale
     * @return string
     */
    private function getOrderSaleFeeDescription(Charge $orderSale)
    {
        if (in_array($orderSale->payment_provider_charge_method, Charge::stripe_methods)) {
            $description = config('app.name') . '-' . $this->business->getName() . ' - Stripe Payment Method(' . $orderSale->id . ')' . ' Fee';
        } elseif($orderSale->payment_provider_charge_method == 'paynow_online') {
            $description = config('app.name') . '-' . $this->business->getName() . ' - PayNow Online Payment Method(' . $orderSale->id . ')' . ' Fee';
        } else {
            $description = config('app.name') . '-' . $this->business->getName() . ' - Cash Payment Method(' . $orderSale->id . ')' . ' Fee';
        }

        $description .= $this->getOrderDescription($orderSale);

        return $description;
    }

    private function getOrderDescription(Charge $charge): string
    {
        return PHP_EOL . 'Order ID: ' . $charge->id
            . PHP_EOL . "Customer Name: " . $charge->customer_name
            . PHP_EOL . "Customer Email: " . $charge->customer_email
            . PHP_EOL . "Remarks: " . $charge->remark;
    }

    /**
     * @param Charge[] $orderSales
     * @param $status
     * @return array[]
     * @throws \ReflectionException
     */
    private function getLineItemsFromIndividualOrdersCharges($orderSales, $status)
    {
        $lineItems = $feeLineItems = [];
        foreach ($orderSales as $orderSale) {
            $fee = $orderSale->getTotalFee();
            if ($status === 'success' && $fee > 0) {
                $feeLineItem = new LineItem();
                $feeLineItem->setDescription($this->getOrderSaleFeeDescription($orderSale));
                $feeLineItem->setQuantity(1);
                $feeLineItem->setTaxType('NONE');
                $feeLineItem->setAccountCode($this->getAccountCodeForLineItem(true));
                $feeLineItem->setUnitAmount(getFormattedAmount($this->business->currency, $fee, false));
                array_push($feeLineItems, $feeLineItem);
            }
            $lineItem = new LineItem();
            $lineItem->setDescription($this->getOrderSaleDescription($orderSale, $status));
            $lineItem->setQuantity(1);
            $lineItem->setTaxType('NONE');
            $lineItem->setAccountCode($this->getAccountCodeForLineItem());
            $lineItem->setUnitAmount(getFormattedAmount($this->business->currency, $orderSale->amount, false));
            array_push($lineItems, $lineItem);
        }

        return [$feeLineItems, $lineItems];
    }

    /**
     * @param Charge[] $orderSales
     * @param $status
     * @return array[]
     * @throws \ReflectionException
     */
    private function getLineItemsFromTotalOrderCharges($orderSales, $status, $date, $dateIsPast)
    {
        $lineItems = $feeLineItems = [];
        $totals = [
            'PAID' => [
                'stripe' => ['fee' => 0, 'amount' => 0,],
                'paynow' => ['fee' => 0, 'amount' => 0,],
                'paynow_online' => ['fee' => 0, 'amount' => 0,],
                'cash' => ['fee' => 0, 'amount' => 0,],
            ],
            'REFUNDED' => [
                'stripe' => ['fee' => 0, 'amount' => 0,],
                'paynow' => ['fee' => 0, 'amount' => 0,],
                'paynow_online' => ['fee' => 0, 'amount' => 0,],
                'cash' => ['fee' => 0, 'amount' => 0,],
            ]
        ];

        $type = $status === 'success' ? 'PAID' : 'REFUNDED';
        foreach ($orderSales as $orderSale) {
            $paymentProvider = $orderSale->getPaymentProviderCode();
            if(!isset($totals[$type][$paymentProvider])) {
                continue;
            }

            $totals[$type][$paymentProvider]['fee'] += $orderSale->getTotalFee();
            $totals[$type][$paymentProvider]['amount'] += $orderSale->amount;
        }

        foreach ($totals as $type => $typeTotals) {
            foreach ($typeTotals as $paymentMethod => $paymentMethodTotals) {
                $paymentMethodName = Charge::getPaymentMethodName($paymentMethod);
                if ($status === 'success' && $paymentMethodTotals['fee'] > 0) {
                    $description = $this->getTotalFeeLineItemDescription($paymentMethodName, $date, $dateIsPast);
                    $feeLineItem = new LineItem();
                    $feeLineItem->setDescription($description);
                    $feeLineItem->setQuantity(1);
                    $feeLineItem->setTaxType('NONE');
                    $feeLineItem->setAccountCode($this->getAccountCodeForLineItem(true));
                    $feeLineItem->setUnitAmount(getFormattedAmount($this->business->currency, $paymentMethodTotals['fee'], false));
                    array_push($feeLineItems, $feeLineItem);
                }

                if($paymentMethodTotals['amount'] > 0) {
                    $lineItem = new LineItem();
                    $lineItem->setDescription($this->getTotalLineItemDescription($paymentMethodName, $status, $date, $dateIsPast));
                    $lineItem->setQuantity(1);
                    $lineItem->setTaxType('NONE');
                    $lineItem->setAccountCode($this->getAccountCodeForLineItem());
                    $lineItem->setUnitAmount(getFormattedAmount($this->business->currency, $paymentMethodTotals['amount'], false));
                    array_push($lineItems, $lineItem);
                }
            }
        }

        return [$feeLineItems, $lineItems];
    }

    public function getTotalLineItemDescription($paymentMethodName, $status, $date, $dateIsPast)
    {
        $description = config('app.name') . '-' . $this->business->getName();
        if($dateIsPast) {
            $description .= '('.$date.' - '.date('Y-m-d').')';
        } else {
            $description .= ' ('.date('Y-m-d', strtotime('-1 days')).') ';
        }

        $status = $status === 'success' ? 'PAID' : 'REFUNDED';

        return "{$description} - {$paymentMethodName} (Total) {$status}";
    }

    public function getTotalFeeLineItemDescription($paymentMethodName, $date, $dateIsPast)
    {
        $description = config('app.name') . '-' . $this->business->getName();
        if($dateIsPast) {
            $description .= '('.$date.' - '.date('Y-m-d').')';
        } else {
            $description .= ' ('.date('Y-m-d', strtotime('-1 days')).') ';
        }

        return "{$description} - {$paymentMethodName} (Total) Fee";
    }

    /**
     * @param Charge[] $orderSales
     * @param $status
     * @return array[]
     * @throws \ReflectionException
     */
    private function getLineItemsFromOrders($orderSales, $status, $date, $dateIsPast)
    {

        if($this->business->xero_invoice_grouping != Xero::INVOICE_GROUPING_TOTAL) {
            return $this->getLineItemsFromIndividualOrdersCharges($orderSales, $status);
        }

        return  $this->getLineItemsFromTotalOrderCharges($orderSales, $status, $date, $dateIsPast);
    }

    /**
     * @param string $status
     * @param array $feeLineItems
     * @param array $lineItems
     * @param $currentContact
     * @param $date
     * @return array
     * @throws \Exception
     */
    private function getInvoicesData(string $status, array $feeLineItems, array $lineItems, $currentContact, $date)
    {
        $invoiceData = [];
        $invoice = new Invoice();
        $invoice->setType(Xero::INVOICE_TYPE);
        $invoice->setContact($currentContact);
        $invoice->setDueDate(new \DateTime($date));
        $invoice->setCurrencyCode(strtoupper($this->business->currency));
        $invoice->setStatus(Xero::INVOICE_STATUS);
        $invoice->setSentToContact(false);
        $invoice->setLineItems($lineItems);
        $invoiceData[] = $invoice;
        if ($status === 'success' && count($feeLineItems) > 0) {
            $feeInvoice = new Invoice();
            $feeInvoice->setType(Xero::INVOICE_TYPE);
            $feeInvoice->setContact($currentContact);
            $feeInvoice->setDueDate(new \DateTime($date));
            $feeInvoice->setCurrencyCode(strtoupper($this->business->currency));
            $feeInvoice->setStatus(Xero::INVOICE_STATUS);
            $feeInvoice->setSentToContact(false);
            $feeInvoice->setLineItems($feeLineItems);
            $invoiceData[] = $feeInvoice;
        }

        return $invoiceData;
    }

    /**
     * @param $accountingApi
     * @param Account $account
     * @param $createdInvoice
     * @return mixed
     * @throws \Exception
     */
    private function createPayment($accountingApi, Account $account, $createdInvoice)
    {
        $payment = new Payment();
        $payment->setAccount($account);
        $payment->setInvoice($createdInvoice);
        $payment->setAmount($createdInvoice->getTotal());
        $payment->setCode($account->getCode());
        $payment->setStatus(Xero::PAYMENT_STATUS);
        $payment->setDate($createdInvoice->getDueDate());
        $payment->setIsReconciled(true);

        return $accountingApi->createPayment($this->business->xero_tenant_id, $payment);
    }

    /**
     * @param array $orderSales
     */
    private function markOrdersAsImported($orderSales)
    {
        foreach ($orderSales as $orderSale) {
            $orderSale->xero_imported = true;
            $orderSale->update();
        }
    }

    /**
     * @param AccountingApi $accountingApi
     * @param array $invoiceData
     * @return mixed
     */
    private function createInvoices(AccountingApi $accountingApi, array $invoiceData)
    {
        $invoices = new Invoices();
        $invoices->setInvoices($invoiceData);
        $apiResponse = $accountingApi->updateOrCreateInvoices($this->business->xero_tenant_id, $invoices);

        return $apiResponse->getInvoices();
    }

    /**
     * @param AccountingApi $accountingApi
     * @param $createdInvoices
     * @param $currentAccount
     * @param $currentFeeAccount
     * @param $status
     * @throws \Exception
     */
    private function createPayments(AccountingApi $accountingApi, $createdInvoices, $currentAccount, $currentFeeAccount, $status)
    {
        foreach ($createdInvoices as $key => $createdInvoice) {
            $account = $status === 'success' && $key === 1
                ? $currentFeeAccount
                : $currentAccount;
            $this->createPayment($accountingApi, $account, $createdInvoice);
        }
    }

    /**
     * @param $status
     * @param $feeLineItems
     * @param $lineItems
     */
    private function updateBusinessLog($status, $feeLineItems, $lineItems)
    {
        $xeroLog = Business\XeroLog::where('business_id', $this->business->getKey())->where('feed_date', date('Y-m-d'))
            ->first();

        if ($status === 'success') {
            $salesCount = count($lineItems);
            $feeCount = count($feeLineItems);

            if (isset($xeroLog->id)) {
                $xeroLog->sales_count = $salesCount;
                $xeroLog->fee_count = $feeCount;
                $xeroLog->update();
            } else {
                $xeroLogs = new Business\XeroLog();
                $xeroLogs->business_id = $this->business->getKey();
                $xeroLogs->sales_count = $salesCount;
                $xeroLogs->fee_count = $feeCount;
                $xeroLogs->feed_date = date('Y-m-d');
                $xeroLogs->save();
            }
        } else {
            $refundCount = count($lineItems);
            if (isset($xeroLog->id)) {
                $xeroLog->refund_count = $refundCount;
                $xeroLog->update();
            } else {
                $xeroLogs = new Business\XeroLog();
                $xeroLogs->business_id = $this->business->getKey();
                $xeroLogs->refund_count = $refundCount;
                $xeroLogs->feed_date = date('Y-m-d');
                $xeroLogs->save();
            }
        }
    }

    private function getAccountCodeForLineItem($isFee = false)
    {
        if($isFee) {
            return $this->currentFeeAccount->getCode();
        }

        return  $this->currentAccount->getCode();
    }
}
