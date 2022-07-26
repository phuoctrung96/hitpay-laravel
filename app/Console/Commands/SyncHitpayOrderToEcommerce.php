<?php

namespace App\Console\Commands;

use App\Business\HotglueIntegration;
use App\Business\HotglueJob;
use App\Business\HotglueOrderTracker;
use App\Business\HotglueProductTracker;
use App\Business\Order;
use App\Business\ProductVariation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class SyncHitpayOrderToEcommerce extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:hitpay-order-to-ecommerce {--order_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync hitpay order to ecommerce';

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
        $orderId = $this->option('order_id');
        if ($order = Order::find($orderId)) {

            if (HotglueOrderTracker::where('business_order_id', $order->id)->first()) {
                Log::info('Hotglue sync hitpay order to ecommerce for order_id: ' . $order->id . ' already done');
                return 0;
            }
            $hotglueIntegration = HotglueIntegration::where('business_id', $order->business_id)
                ->where('flow', config('services.hotglue.ecommerce_flow_id'))
                ->where('connected', true)
                ->where('sync_all_hitpay_orders', true)
                ->first();

            if ($hotglueIntegration) {
                Log::info('Hotglue sync hitpay order to ecommerce for order_id: ' . $order->id . ' start process');

                $orderedProducts = $order->products;
                $originalProducts = ProductVariation::with('product')->whereIn('id', $orderedProducts->pluck('business_product_id'))->get();

                $orderedItems = [];
                foreach ($orderedProducts as $orderedProduct) {
                    $product = $originalProducts->where('id', $orderedProduct->business_product_id)->first();

                    if ($itemId = $product->shopify_inventory_item_id) {
                        $hotglueProducts = HotglueProductTracker::with('hotglueJob')->where('item_id', $itemId)->first();

                        if ($hotglueProducts) {
                            $orderedItems[] = [
                                'variant_id' => $itemId,
                                "quantity" => (int) $orderedProduct->quantity
                            ];
                        }
                    }
                }

                if (count($orderedItems) > 0) {
                    Log::info('Hotglue sync hitpay order to ecommerce for order_id: ' . $order->id . ' has valid products');
                    $client = new Client;
                    $url = config('services.hotglue.api_host') . '/' . config('services.hotglue.env_id') . '/' . config('services.hotglue.product_flow_id') . '/' . $order->business_id . '/jobs';
                    $response = $client->post($url, [
                        'body' => json_encode([
                            'job_name' => 'sync-hitpay-order',
                            'state' => [
                                'orders' => [
                                    [
                                        'line_items' => $orderedItems
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
                        $hotglueJob = HotglueJob::firstOrCreate([
                            'job_id' => $response['job_id']
                        ], [
                            'hotglue_integration_id' => $hotglueIntegration->id,
                            'job_id' => $response['job_id'],
                            'job_name' => $response['job_name'],
                            'status' => $response['status'],
                            'aws_path' => $response['s3_root'],
                            'job_type' => HotglueJob::ORDER_SYNC
                        ]);

                        HotglueOrderTracker::firstOrCreate([
                            'hotglue_job_id' => $hotglueJob->id,
                            'business_order_id' => $order->id
                        ], [
                            'hotglue_job_id' => $hotglueJob->id,
                            'business_order_id' => $order->id
                        ]);

                        Log::info('Hotglue sync hitpay order to ecommerce for order_id: ' . $order->id . ' request successfully sent');
                    }
                } else {
                    Log::info('Hotglue sync hitpay order to ecommerce for order_id: ' . $order->id . ' has no valid products');
                }

                Log::info('Hotglue sync hitpay order to ecommerce for order_id: ' . $order->id . ' end process');
            }
        } else {
            Log::critical('Hotglue sync hitpay order to ecommerce for order_id: ' . $orderId . ' not found');
        }

        return 0;
    }
}
