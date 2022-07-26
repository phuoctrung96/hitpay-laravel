<?php

namespace App\Console\Commands;

use App\Business;
use App\Imports\InvoiceFeedImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProceedInvoiceFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'proceed:invoiceFeed {--business_id=*} {--file_path=*}';

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
        $businessId = $this->option('business_id');
        $path = $this->option('file_path')[0];

        $business = Business::where('id', $businessId)->first();
        if (!isset($business->id)) {
            return 1;
        }

        Excel::import(new InvoiceFeedImport($business), $path);

        return 0;
    }
}
