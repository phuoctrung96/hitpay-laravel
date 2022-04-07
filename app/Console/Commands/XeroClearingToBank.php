<?php

namespace App\Console\Commands;

use App\Enumerations\Business\ChargeStatus;
use App\Business;
use App\Services\XeroApiFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use XeroAPI\XeroPHP\ApiException;
use XeroAPI\XeroPHP\Models\Accounting\Account;
use XeroAPI\XeroPHP\Models\Accounting\BankTransfer;
use XeroAPI\XeroPHP\Models\Accounting\BankTransfers;

class XeroClearingToBank extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xero:send-clear-to-bank';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $businesses = Business::query()
            ->whereNotNull('xero_payment_fee_account_id')
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
            $api = XeroApiFactory::makeAccountingApi($business);
            $amount = $this->getAmount($business);

            if($transfers = $this->makeBankTransfersContainer($business, $amount)) {
                $api->createBankTransfer(
                    $business->xero_tenant_id,
                    $transfers
                );

                $this->info('Send amount '.$amount.' to bank account for business #' . $business->id);
            }
        } catch (ApiException $exception) {
            dump($exception->getResponseBody(), $exception);
            Log::error($exception);
        } catch (\Exception $exception) {
            dump($exception);
            Log::error($exception);
        }
    }

    /**
     * @param Business $business
     * @param float $amount
     * @return BankTransfers|null
     */
    private function makeBankTransfersContainer(Business $business, float $amount): ?BankTransfers
    {
        if($amount == 0) {
            return null;
        }

        $transfer = (new BankTransfer())
            ->setAmount($amount)
            ->setFromBankAccount($this->getFromBankAccount($business))
            ->setToBankAccount($this->getToBankAccount($business));

        $transfers = new BankTransfers();
        $transfers->setBankTransfers([$transfer]);

        return $transfers;
    }

    private function getFromBankAccount(Business $business): Account
    {
        $api = XeroApiFactory::makeAccountingApi($business);
        return $api->getAccount($business->xero_tenant_id, $business->xero_bank_account_id)
            ->getAccounts()[0];
    }

    private function getToBankAccount(Business $business): Account
    {
        $api = XeroApiFactory::makeAccountingApi($business);
        return $api->getAccount($business->xero_tenant_id, $business->xero_payout_account_id)
            ->getAccounts()[0];
    }

    private function getAmount(Business $business): float
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

        return ($received - $spend) / 100;
    }
}
