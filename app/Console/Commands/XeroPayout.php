<?php

namespace App\Console\Commands;

use App\Business;
use App\Services\XeroApiFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use XeroAPI\XeroPHP\ApiException;
use XeroAPI\XeroPHP\Models\Accounting\Account;
use XeroAPI\XeroPHP\Models\Accounting\BankTransfer;
use XeroAPI\XeroPHP\Models\Accounting\BankTransfers;

class XeroPayout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xero:payout';

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
    public function handle() : int
    {
        $businesses = Business::whereNotNull('xero_payout_account_id')
            ->get();
        foreach ($businesses as $business) {
            $this->info($business->id . ', ' . $business->xero_email);
            $this->makePayout($business);
        }

        return 0;
    }

    private function makePayout(Business $business)
    {
        try {
            $api = XeroApiFactory::makeAccountingApi($business);
            if($transfers = $this->makeBankTransfersContainer($business)) {
                $api->createBankTransfer(
                    $business->xero_tenant_id,
                    $transfers
                );

                $this->info('Payout made for business #' . $business->id);
            }
        } catch (ApiException $exception) {
            $this->error($exception->getMessage());
            Log::error($exception);
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
            Log::error($exception);
        }
    }

    /**
     * @param Business $business
     * @return BankTransfers
     */
    private function makeBankTransfersContainer(Business $business): ?BankTransfers
    {
        $amount = $this->getPayoutAmount($business);

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

    private function getPayoutAmount(Business $business): float
    {
        $amount = 0;
        $fees = 0;
        $charges = $business->charges()
            ->where('currency', 'sgd')
            ->whereDate('closed_at', '=', date('Y-m-d', strtotime('-1 day')))
            ->get();

        /** @var Business\Charge $charge */
        foreach ($charges as $charge) {
            if(!$charge->isForXeroInvoice()) {
                continue;
            }
            $amount += $charge->amount;
            $fees += $charge->getTotalFee();
        }

        return ($amount - $fees) / 100;
    }

}
