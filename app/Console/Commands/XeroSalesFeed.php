<?php

namespace App\Console\Commands;

use App\Business;
use App\Business\Xero;
use App\Enumerations\Business\ChargeStatus;
use App\Services\XeroSalesService;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use XeroAPI\XeroPHP\Api\AccountingApi;
use XeroAPI\XeroPHP\Configuration;
use XeroAPI\XeroPHP\Models\Accounting\Account;
use XeroAPI\XeroPHP\Models\Accounting\Contact;
use XeroAPI\XeroPHP\Models\Accounting\Contacts;
use XeroAPI\XeroPHP\Models\Accounting\Invoice;
use XeroAPI\XeroPHP\Models\Accounting\Invoices;
use XeroAPI\XeroPHP\Models\Accounting\LineItem;
use XeroAPI\XeroPHP\Models\Accounting\Payment;

class XeroSalesFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'proceed:xero-sales-feed {--status=*}';

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
        $status = $this->option('status')[0];
        if (!isset($status))
        {
            echo 'status argument is required';
            return 1;
        }
        if ($status != 'success' && $status != 'refund')
        {
            return 1;
        }

        $businesses = Business::query()
            ->whereNotNull('xero_refresh_token')
            ->where('xero_refresh_token', '!=', '')
            ->get();
        if (!count($businesses)) {
            $this->warn('No business with xero token found');
            return 1;
        }

        collect($businesses)->map(function ($business) use ($status) {
            try {
                $this->info($business->id);
                $xeroSalesServices = new XeroSalesService($business);
                if ($xeroSalesServices->shouldSync()) {
                    $xeroSalesServices->sync($status);
                    $this->info('Business #' . $business->id . ' data was synchronized');
                } else {
                    $this->warn('Business #' . $business->id . ' was run today. No sync needed.');
                }
            } catch (\Throwable $exception) {
                $this->error($exception->getMessage());
                $this->table(['Callable', 'Path'], array_map(function($traceBack) {
                    return [@$traceBack['class'] . '@' . @$traceBack['function'], @$traceBack['file'].':'.@$traceBack['line']];
                }, $exception->getTrace()));
                dump($exception);
                Log::error($exception);
            }
        });

        return 0;
    }
}
