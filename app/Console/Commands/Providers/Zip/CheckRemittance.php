<?php

namespace App\Console\Commands\Providers\Zip;

use Illuminate\Console\Command;
use App\Jobs\Providers\Zip\CheckRemittance as CheckRemittanceJob;

class CheckRemittance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zip:check_remittance {date_from?} {date_to?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Zip remittance';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      CheckRemittanceJob::dispatchNow($this->argument('date_from'), $this->argument('date_to'));
    }
}