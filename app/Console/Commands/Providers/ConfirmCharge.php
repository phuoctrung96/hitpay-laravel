<?php

namespace App\Console\Commands\Providers;

use App\Business\Charge;
use Illuminate\Console\Command;

class ConfirmCharge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'charge:confirm {charge_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Confirm charge (GrabPay, Shopee)';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      $charge_id = $this->argument('charge_id');

      $charge = Charge::find($charge_id);

      if ($charge instanceof Charge) {
        $charge->business->confirmCharge($charge);
      } else {
        echo "Error: Charge " . $charge_id . " not found.";

        return 1;
      }

      return 0;
    }
}
