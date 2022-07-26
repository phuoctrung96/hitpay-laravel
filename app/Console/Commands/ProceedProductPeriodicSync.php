<?php

namespace App\Console\Commands;

use App\Business;
use App\Business\HotglueJob;
use App\Business\HotglueIntegration;
use App\Business\HotglueProductTracker;
use App\Http\Controllers\Dashboard\Business\HotglueController;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProceedProductPeriodicSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay-to-hotglue:periodic-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hitpay to ecommerce periodic sync';

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
        Log::info('Hitpay to ecommerce periodic sync start process');

        $hotglueIntegrations = HotglueIntegration::where('flow', config('services.hotglue.product_flow_id'))
            ->where('connected', true)
            ->where('periodic_sync', true)
            ->get();

        if ($hotglueIntegrations->count() > 0) {
            $hotglueController = new HotglueController;
            foreach($hotglueIntegrations as $hotglue) {
                $business = Business::find($hotglue->business_id);

                $products = $business->products()->with('images', 'variations')
                    ->whereDate('created_at', '>=', Carbon::now()->subDay()->startOfDay()->toDateTimeString())
                    ->get();

                $hotglueController->apiRequestProduct($products, $business->id, $hotglue['id'], $hotglue['source'], $hotglue['flow'], 'periodic-sync');
            }
        } else {
            Log::info('Hitpay to ecommerce periodic sync. No periodic job to process');
        }
        Log::info('Hitpay to ecommerce periodic sync end process');

        return 0;
    }
}
