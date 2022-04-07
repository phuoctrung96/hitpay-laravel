<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Business;
use Illuminate\Support\Facades\Log;

class EnableShop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enable:shop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all shops which are offline and if the enable datetime is set and this time has come we enable shop';

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
        $businesses = Business::all();

        foreach ($businesses as $business){
            if (!$business->shop_state && $business->enable_datetime != null){
                $dateTimeNow = Carbon::now()->format('Y-m-d h:i');
                $enableDateTime = Carbon::parse($business->enable_datetime)->format('Y-m-d h:i');
                $this->info($dateTimeNow);
                $this->info($enableDateTime);
                if ($dateTimeNow == $enableDateTime){
                    $business->shopSettings()->update(['shop_state' => 1, 'enable_datetime' => null]);
                }
            }
        }
       $this->info('Command work');
    }
}
