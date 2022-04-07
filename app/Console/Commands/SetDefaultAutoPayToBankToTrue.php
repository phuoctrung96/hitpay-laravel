<?php

namespace App\Console\Commands;

use App\Business;
use Illuminate\Console\Command;

class SetDefaultAutoPayToBankToTrue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:set-default-auto-pay-to-bank-to-true';

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
     */
    public function handle()
    {
        Business::query()->update([
            'auto_pay_to_bank' => true,
        ]);
    }
}
