<?php

namespace App\Console\Commands;

use App\Business;
use HitPay\Shopify\Shopify;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class DisableAllShopifySync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:disable-shopify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable all Shopify sync';

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
        $businesses = Business::whereNotNull('shopify_id')->get();

        $this->info($businesses->count().' shopify account detected.');

        $businesses->each(function (Business $business) {
            try {
                $this->info('Processing: '.$business->getKey().' @ '.$business->name);

                $shopify = new Shopify($business->shopify_domain, $business->shopify_token);
                $shopify->uninstall();

                $business->productBases()->whereNotNull('shopify_id')->forceDelete();

                $business->shopify_data = null;
                $business->shopify_id = null;
                $business->shopify_domain = null;
                $business->shopify_token = null;
                $business->shopify_location_id = null;

                $business->save();

                $this->info('Processed.');
            } catch (Throwable $exception) {
                $message = 'Removing shopify sync for business '.$business->getKey().' incomplete.';

                Log::critical($message);

                $this->error($message);
                $this->error(get_class($exception));
                $this->error('File   : '.$exception->getFile());
                $this->error('Line   : '.$exception->getLine());
                $this->error('Message: '.$exception->getMessage());
                $this->error('Trace  : '.$exception->getTraceAsString());
                $this->error('');
                $this->error('');
                $this->error('');
                $this->error('');
                $this->error('');
            }
        });
    }
}
