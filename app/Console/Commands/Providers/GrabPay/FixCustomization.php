<?php

namespace App\Console\Commands\Providers\GrabPay;

use App\Business\Charge;
use Illuminate\Console\Command;
use App\Helpers\Customization;

class FixCustomization extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grabpay:fix_customization {business_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replace old GrabPay with GrabPay direct in customization';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): int
    {
      Customization::replaceOldGrabPay($this->argument('business_id'));
      return 0;
    }
}
