<?php

namespace App\Console\Commands;

use App\Business;
use App\Business\HotglueJob;
use App\Business\HotglueIntegration;
use App\Business\HotglueProductTracker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class UpdateHotglueProductQuantity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:hotglue-product-quantity {--business_id=} {--product_id=} {--ordered_quantity=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update hotglue product quantity';

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
        $businessId = $this->option('business_id');
        $productId = $this->option('product_id');
        $orderedQuantity = $this->option('ordered_quantity');

        $business = Business::find($businessId);
        if (!$business) {
            Log::critical('Hotglue update product quanity for business ' . $businessId . ' not found');
            return;
        }

        $product = $business->productBases()->findOrFail($productId);
        if ($product->isProduct()) {
            $product = $business->products()->findOrFail($product->getKey());
        } else {
            $product = $business->products()->findOrFail($product->parent_id);
        }

        if ($sku = $product->stock_keeping_unit) {
            $hotglueProducts = HotglueProductTracker::with('hotglueJob')->whereStockKeepingUnit($sku)->first();

            if ($hotglueProducts) {
                Log::info('Hotglue update product sku: ' . $sku . ' quantity: ' . $orderedQuantity . ' for business ' . $businessId . ' start process');
    
                $client = new Client;
                $url = config('services.hotglue.api_host') . '/' . config('services.hotglue.env_id') . '/' . config('services.hotglue.product_flow_id') . '/' . $business->id . '/jobs';
                $response = $client->post($url, [
                    'body' => json_encode([
                        'job_name' => 'update-qty-' . config('services.hotglue.product_flow_id'),
                        'state' => [
                            'inventory' => [
                                [
                                    'sku' => $sku,
                                    'inventory_quantity' => (int) $orderedQuantity
                                ]
                            ]
                        ]
                    ]),
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'x-api-key'=> config('services.hotglue.public_api_key')
                    ],
                ]);
    
                $response = json_decode((string) $response->getBody(), true);
    
                if ($response) {
                    $hotglueIntegration = $business->hotglueIntegration()->whereType(HotglueJob::PRODUCTS)->where('connected', true)->first();
    
                    HotglueJob::firstOrCreate([
                        'job_id' => $response['job_id']
                    ], [
                        'hotglue_integration_id' => $hotglueIntegration->id,
                        'job_id' => $response['job_id'],
                        'job_name' => $response['job_name'],
                        'status' => $response['status'],
                        'aws_path' => $response['s3_root']
                    ]);
                    Log::info('Hotglue update product job successfully created for sku: ' . $sku . ' quantity: ' . $orderedQuantity);
                } else {
                    Log::critical('Hotglue update product failed to created job for sku: ' . $sku . ' quantity: ' . $orderedQuantity);
                }
    
                Log::info('Hotglue update product sku: ' . $sku . ' quantity: ' . $orderedQuantity . ' for business ' . $businessId . ' end process');
            }
        }
    }
}
