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
    public function handle() : int
    {
        $businessId = $this->option('business_id');
        $productId = $this->option('product_id');
        $orderedQuantity = $this->option('ordered_quantity');

        $business = Business::find($businessId);
        if (!$business) {
            Log::critical('Hotglue update product quanity for business ' . $businessId . ' not found');
            return 1;
        }

        $product = $business->productBases()->findOrFail($productId);

        if (!$product->shopify_inventory_item_id) {
            $product = $business->products()->findOrFail($product->parent_id);
        }

        if ($itemId = $product->shopify_inventory_item_id) {
            $hotglueProducts = HotglueProductTracker::with('hotglueJob')->where('item_id', $itemId)->first();
            $hotglueIntegration = HotglueIntegration::find($hotglueProducts->hotglueJob->hotglue_integration_id);

            if ($hotglueProducts && $hotglueIntegration) {
                Log::info('Hotglue update product item_id: ' . $itemId . ' quantity: ' . $orderedQuantity . ' for business ' . $businessId . ' start process');

                $client = new Client;
                $url = config('services.hotglue.api_host') . '/' . config('services.hotglue.env_id') . '/' . config('services.hotglue.product_flow_id') . '/' . $business->id . '/jobs';
                $response = $client->post($url, [
                    'body' => json_encode([
                        'job_name' => 'update-qty-' . config('services.hotglue.product_flow_id'),
                        'state' => [
                            'inventory' => [
                                [
                                    'variant_id' => $itemId,
                                    'location_id' => (string) $hotglueIntegration->selected_location_id,
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
                    HotglueJob::firstOrCreate([
                        'job_id' => $response['job_id']
                    ], [
                        'hotglue_integration_id' => $hotglueIntegration->id,
                        'job_id' => $response['job_id'],
                        'job_name' => $response['job_name'],
                        'status' => $response['status'],
                        'aws_path' => $response['s3_root'],
                        'job_type' => HotglueJob::QUANTITY_SYNC
                    ]);
                    Log::info('Hotglue update product job successfully created for item_id: ' . $itemId . ' quantity: ' . $orderedQuantity);
                } else {
                    Log::critical('Hotglue update product failed to created job item_id: ' . $itemId . ' quantity: ' . $orderedQuantity);
                }

                Log::info('Hotglue update product item_id: ' . $itemId . ' quantity: ' . $orderedQuantity . ' for business ' . $businessId . ' end process');
            }
        }

        return 0;
    }
}
