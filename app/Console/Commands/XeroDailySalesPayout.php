<?php

namespace App\Console\Commands;

use App\Business;
use App\Enumerations\Business\ChargeStatus;
use App\Services\XeroApiFactory;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use XeroAPI\XeroPHP\Api\AccountingApi;
use XeroAPI\XeroPHP\ApiException;
use XeroAPI\XeroPHP\Models\Accounting\BankTransaction;
use XeroAPI\XeroPHP\Models\Accounting\BankTransactions;
use XeroAPI\XeroPHP\Models\Accounting\Contact;
use XeroAPI\XeroPHP\Models\Accounting\Contacts;
use XeroAPI\XeroPHP\Models\Accounting\LineItem;

class XeroDailySalesPayout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xero:daily-sales-payout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    /**
     * @var AccountingApi
     */
    private $api;

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
        $businesses = Business::whereNotNull('xero_payment_fee_account_id')
            ->get();

        foreach ($businesses as $business) {
            $this->info($business->id . ', ' . $business->xero_email);
            try {
                $this->api = XeroApiFactory::makeAccountingApi($business);
                $this->makePayout($business);
            } catch (\Exception $exception) {
                $this->error($exception->getMessage());
                Log::error($exception);
            }
        }
    }

    private function makePayout(Business $business)
    {
        try {
            [$spend, $received] = $this->getSpendReceived($business);

            if($spend > 0) {
                $this->createSpendTransaction($business, $spend);
            }

            if($received > 0) {
                $this->createReceivedTransaction($business, $received);
            }
        } catch (ApiException $exception) {
            dump($exception->getResponseBody(), $exception);
            Log::error($exception);
        } catch (\Exception $exception) {
            dump($exception);
            Log::error($exception);
        }
    }

    private function createSpendTransaction(Business $business, float $amount)
    {
        $this->createBankTransactions($business, $amount, 'spend');

        $this->info('Spent payment made for business #' . $business->id);
    }

    private function createReceivedTransaction(Business $business, float $amount)
    {
        $response = $this->createBankTransactions($business, $amount, 'received');

        $this->info('Received payment made for business #' . $business->id);
    }

    private function getSpendReceived(Business $business): array
    {
        $orderSales = $business->charges()
            ->with('target')
            ->where('currency', 'sgd')
            ->where('status', ChargeStatus::SUCCEEDED)
            ->whereDate('closed_at', '=', date('Y-m-d', strtotime('-1 day')))
            ->get();

        $spend = 0;
        $received = 0;

        /** @var Business\Charge $orderSale */
        foreach ($orderSales as $orderSale) {
            if($orderSale->isForXeroInvoice()) {
                continue;
            }

            $spend += $orderSale->getTotalFee();
            $received += $orderSale->amount;
        }

        return [$spend / 100, $received / 100];
    }

    private function getAccount(string $tenantId, string $id)
    {
        return $this->api->getAccount($tenantId, $id)
            ->getAccounts()[0];
    }

    private function getContact(Business $business)
    {
        $contact = new Contact();
        $currentContact = null;
        if (empty($this->business->xero_contact_id)) {
            $contact->setName(config('app.name') . ' - ' . $business->getName() . ' - ' . now()->toDateTimeString() );
            $arr_contacts = [];
            array_push($arr_contacts, $contact);
            $contacts = new Contacts();
            $contacts->setContacts($arr_contacts);
            $apiResponse = $this->api->createContacts($business->xero_tenant_id, $contacts);
            $currentContact = $apiResponse->getContacts()[0];
            $business->xero_contact_id = $currentContact->getContactId();
            $business->update();

        } else {
            $currentContact = $this->api->getContact($business->xero_tenant_id, $business->xero_contact_id);
            $currentContact = $currentContact->getContacts()[0];
        }

        return $currentContact;
    }

    private function createBankTransactions(Business $business, float $amount, string $type)
    {
        $bankAccount = $this->getBankAccount($business);
        $account = $this->getAccount(
            $business->xero_tenant_id,
            $type == 'spend' ? $business->xero_fee_account_id : $business->xero_account_id
        );
        $contact = $this->getContact($business);

        $spendMoneyTransaction = new BankTransaction();

        $lineItem = new LineItem();
        $lineItem->setDescription('Payment ' . $type);
        if($account) {
            $lineItem->setAccountCode($account->getCode());
        }
        $lineItem->setUnitAmount($amount);

        $spendMoneyTransaction
            ->setDate(new DateTime(date('Y-m-d')))
            ->setType($type == 'spend' ? BankTransaction::TYPE_SPEND : BankTransaction::TYPE_RECEIVE)
            ->setIsReconciled(true)
            ->setBankAccount($bankAccount)
            ->setContact($contact)
            ->setLineItems([$lineItem]);

        $transactions = new BankTransactions();
        $transactions->setBankTransactions([$spendMoneyTransaction]);

        return $this->api->createBankTransactions($business->xero_tenant_id, $transactions);
    }

    private function getBankAccount($business)
    {
        $account = $this->api->getAccount($business->xero_tenant_id, $business->xero_bank_account_id);
        return $account->getAccounts()[0];
    }
}
