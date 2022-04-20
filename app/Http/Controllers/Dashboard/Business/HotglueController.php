<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Business\HotglueJob;
use App\Business\HotglueIntegration;
use App\Business\HotglueProductTracker;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class HotglueController extends Controller
{
    private $client;

    public function __construct()
    {
        $this->client = new Client;
    }

    public function index(Business $business)
    {
        Gate::inspect('view', $business)->authorize();
        return Response::view('dashboard.business.hotglue.index', compact('business'));
    }

    public function sourceConnected(Business $business, Request $request)
    {
        Gate::inspect('manage', $business)->authorize();

        $request->validate([
            'source' => 'required|string|max:255',
            'flow' => 'required|string|max:255'
        ]);

        $data = $request->all();
        $logEcommerceTemplate = $this->generateLogTemplate(HotglueJob::ECOMMERCE, $data['source']);
        Log::info($logEcommerceTemplate . ' successfully connected. Start Hitpay process for business_id: ' . $business->id);

        $ecommerceFlow = HotglueIntegration::whereBusinessId($business->id)
            ->whereSource($data['source'])
            ->whereFlow($data['flow'])
            ->whereType(HotglueJob::ECOMMERCE)
            ->where('connected', false)
            ->first();

        if ($ecommerceFlow) {
            $ecommerceFlow->connected = true;
            $ecommerceFlow->update();
            Log::info($logEcommerceTemplate . ' successfully activated');
        } else {
            $ecommerceFlow = HotglueIntegration::firstOrCreate([
                'business_id' => $business->id,
                'source' => $data['source'],
                'flow' => $data['flow'], 
                'type' => HotglueJob::ECOMMERCE
            ]);
            Log::info($logEcommerceTemplate . ' successfully added');
        }

        $ecommerceResponse = $this->apiRequestEcommerce($business->id, $ecommerceFlow->id, $data['source'], $data['flow'], 'initial');

        if ($ecommerceResponse) {
            $url = config('services.hotglue.api_host') . '/' . config('services.hotglue.env_id') . '/' . config('services.hotglue.ecommerce_flow_id') . '/' . $business->id . '/jobs/schedule';
            $ecommerceFlowSched = $this->client->put($url, [
                'body' => json_encode([
                    'state' => 'ENABLED',
                    'schedule_expression' => 'cron(0 0 ? * * *)'
                ]),
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'x-api-key'=> config('services.hotglue.public_api_key')
                ],
            ]);
            $ecommerceFlowSched = json_decode((string) $ecommerceFlowSched->getBody(), true);
            Log::info($logEcommerceTemplate . ' schedule ecommerce flow body ' . json_encode($ecommerceFlowSched));
            Log::info($logEcommerceTemplate . ' hotglue job successfully created');
        } else {
            Log::critical($logEcommerceTemplate . ' failed to create hotglue job for business_id ' . $business->id);
        }
        Log::info($logEcommerceTemplate . '. End Hitpay process for business_id: ' . $business->id);

        $logProductTemplate = $this->generateLogTemplate(HotglueJob::PRODUCTS, $data['source']);
        try {
            $url = config('services.hotglue.api_host') . '/' . config('services.hotglue.env_id') . '/' . config('services.hotglue.product_flow_id') . '/' . $business->id . '/linkedTargets';
            $this->client->post($url, [
                'body' => json_encode([
                    'target' => [
                        'target' => $data['source'],
                        'symlink' => [
                            'id' => config('services.hotglue.ecommerce_flow_id')
                        ]
                    ]
                ]),
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'x-api-key'=> config('services.hotglue.public_api_key')
                ],
            ]);
            Log::info($logProductTemplate . ' successfully connected. Start Hitpay process for business_id: ' . $business->id);
        } catch (\Exception $e) {
            Log::critical($logProductTemplate . ' failed to connect with error code: ' . $e->getCode() . ' for business_id: ' . $business->id);
        }

        $productFlow = HotglueIntegration::whereBusinessId($business->id)
            ->whereSource($data['source'])
            ->whereFlow(config('services.hotglue.product_flow_id'))
            ->whereType(HotglueJob::PRODUCTS)
            ->where('connected', true)
            ->first();

        if ($productFlow) {
            $productFlow->connected = false;
            $productFlow->update();
            Log::info($logProductTemplate . ' successfully activated');
        } else {
            HotglueIntegration::firstOrCreate([
                'business_id' => $business->id,
                'source' => $data['source'],
                'flow' => config('services.hotglue.product_flow_id'), 
                'type' => HotglueJob::PRODUCTS,
                'connected' => false
            ]);
            Log::info($logProductTemplate . ' successfully added');
        }
        Log::info($logProductTemplate . '. End Hitpay process for business_id: ' . $business->id);

        return $business->hotglueIntegration()->with('jobInProgress')->get();
    }

    public function sourceDisconnected(Business $business, Request $request)
    {
        Gate::inspect('manage', $business)->authorize();

        if ($hotglueIntegration = HotglueIntegration::find($request->id)) {
            $logTemplate = $this->generateLogTemplate($hotglueIntegration->type, $hotglueIntegration->source);
            Log::info($logTemplate . ' start disconnection for business_id: ' . $business->id);
            if ($hotglueIntegration->type == HotglueJob::ECOMMERCE) {
                try {
                    $url = config('services.hotglue.api_host') . '/tenant/' . config('services.hotglue.env_id') . '/' . $business->id;
                    $this->client->delete($url, [
                        'headers' => [
                            'Accept' => 'application/json',
                            'x-api-key'=> config('services.hotglue.secret_api_key')
                        ],
                    ]);
                    Log::info($logTemplate . ' successfully disconnected');
                } catch (\Exception $e) {
                    Log::critical($logTemplate . ' failed to disconnect with error code: ' . $e->getCode() . ' for business_id: ' . $business->id);
                }
            } else {
                Log::info($logTemplate . ' successfully disconnected');
            }

            $hotglueIntegration->connected = false;
            $hotglueIntegration->update();
            Log::info($logTemplate . ' successfully deactivated');
        }

        Log::info($logTemplate . ' end disconnection for business_id: ' . $business->id);
        if ($business->hotglueIntegration()->whereType(HotglueJob::ECOMMERCE)->whereConnected(true)->first()) {
            return $business->hotglueIntegration()->with('jobInProgress')->get();
        }

        return [];
    }

    public function targetLinked(Business $business, Request $request)
    {
        Gate::inspect('manage', $business)->authorize();

        $request->validate(['flow' => 'required|string|max:255']);

        $data = $request->all();
        $logProductTemplate = $this->generateLogTemplate(HotglueJob::PRODUCTS, $data['target']);
        Log::info($logProductTemplate . ' successfully connected. Start Hitpay process for business_id: ' . $business->id);

        $hotglue = HotglueIntegration::whereBusinessId($business->id)
            ->whereSource($data['target'])
            ->whereType(HotglueJob::PRODUCTS)
            ->where('connected', false)
            ->first();

        $hotglue->connected = true;
        $hotglue->update();
        Log::info($logProductTemplate . ' successfully activated');

        $products = $business->products()->with('images', 'variations')->get();

        $this->apiRequestProduct($products, $business->id, $hotglue->id, $data['target'], $data['flow'], 'initial');

        Log::info($logProductTemplate . '. End Hitpay process for business_id: ' . $business->id);
        return $business->hotglueIntegration()->with('jobInProgress')->get();
    }

    public function productPeriodicSync(Business $business, Request $request)
    {
        Gate::inspect('manage', $business)->authorize();

        Log::info('Hotglue integration periodic sync from hitpay to shopify start process for business_id: ' . $business->id);
        if ($hotglueIntegration = HotglueIntegration::find($request->id)) {
            if ($request->periodic_sync) {
                Log::info('Hotglue integration enabling periodic sync from hitpay to shopify for business_id: ' . $business->id);
            } else {
                Log::info('Hotglue integration disabling periodic sync from hitpay to shopify for business_id: ' . $business->id);
            }

            $hotglueIntegration->periodic_sync = $request->periodic_sync;
            $hotglueIntegration->update();
        }
        Log::info('Hotglue integration periodic sync from hitpay to shopify end process for business_id: ' . $business->id);
    }

    public function syncAllHitpayOrders(Business $business, Request $request)
    {
        Gate::inspect('manage', $business)->authorize();

        Log::info('Hotglue integration sync all hitpay orders start process for business_id: ' . $business->id);
        if ($hotglueIntegration = HotglueIntegration::find($request->id)) {
            if ($request->sync_all_hitpay_orders) {
                Log::info('Hotglue integration enabling sync all hitpay orders to shopify for business_id: ' . $business->id);
            } else {
                Log::info('Hotglue integration disabling sync all hitpay orders to shopify for business_id: ' . $business->id);
            }

            $hotglueIntegration->sync_all_hitpay_orders = $request->sync_all_hitpay_orders;
            $hotglueIntegration->update();
        }
        Log::info('Hotglue integration sync all hitpay orders end process for business_id: ' . $business->id);
    }

    public function syncNow(Business $business, Request $request)
    {
        Gate::inspect('manage', $business)->authorize();

        $flowName = $request->flow === config('services.hotglue.ecommerce_flow_id') ? HotglueJob::ECOMMERCE : HotglueJob::PRODUCTS;

        $logTemplate = $this->generateLogTemplate($flowName, $request->source);

        Log::info($logTemplate . ' sync now start process for business_id: ' . $business->id);

        try {
            if ($flowName === HotglueJob::ECOMMERCE) {
                $this->apiRequestEcommerce($business->id, $request->id, $request->source, $request->flow, 'sync-now');
            } else {
                $products = $business->products()->with('images', 'variations')
                    ->whereDate('created_at', '>=', Carbon::now()->subDay()->startOfDay()->toDateTimeString())
                    ->get();

                $this->apiRequestProduct($products, $business->id, $request->id, $request->source, $request->flow, 'sync-now');
            }
            Log::info($logTemplate . ' sync now processed successfully for business_id: ' . $business->id);
        } catch (\Exception $e) {
            Log::critical($logTemplate . ' sync now processed failed with error code: ' . $e->getCode() . ' for business_id: ' . $business->id);
        }

        Log::info($logTemplate . ' sync now end process for business_id: ' . $business->id);

        return $business->hotglueIntegration()->with('jobInProgress')->get();
    }

    private function apiRequestEcommerce($business_id, $integration_id, $source, $flow, $prefix = null)
    {
        $prefix = $prefix ? $prefix . '-' : null;
        $jobName = $prefix . $source . '-' . $flow; 

        $url = config('services.hotglue.api_host') . '/' . config('services.hotglue.env_id') . '/' . $flow . '/' . $business_id . '/jobs';
        $response = $this->client->post($url, [
            'body' => json_encode(['job_name' => $jobName]),
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
                'hotglue_integration_id' => $integration_id,
                'job_id' => $response['job_id'],
                'job_name' => $response['job_name'],
                'status' => $response['status'],
                'aws_path' => $response['s3_root']
            ]);
        }

        return true;
    }

    public function apiRequestProduct($products, $business_id, $integration_id, $source, $flow, $prefix = null)
    {
        $logProductTemplate = $this->generateLogTemplate(HotglueJob::PRODUCTS, $source);
        $storageDefaultDisk = Storage::getDefaultDriver();

        $prepareProducts = [];
        foreach($products as $product) {
            $hotglueProducts = HotglueProductTracker::whereStockKeepingUnit($product->stock_keeping_unit)
                ->whereNotNull('stock_keeping_unit')
                ->get();

            $images = [];
            $variations = [];
            $tempProducts = [];
            if ($hotglueProducts->count() === 0) {
                if ($product->images_count > 0) {
                    foreach ($product->images as $image) {
                        $images[] = [ "src" => Storage::disk($storageDefaultDisk)->url($image->path) ];
                    }
                }

                if ($product->variations_count > 0) {
                    if ($source == HotglueJob::WOOCOMMERCE) {
                        foreach ($product->variations as $variant) {
                            $variations[] = [
                                "option1" => $variant->description ?? $product->name,
                                "price" => substr($variant->price, 0, -2),
                                "sku" => $product->stock_keeping_unit,
                                "inventory_management" => $source,
                                "inventory_quantity" => $variant->quantity
                            ];
                        }
                    } else {
                        foreach ($product->variations as $variant) {
                            $variations[] = [
                                "id" => $variant->id,
                                "product_id" => $product->id,
                                "title" => $variant->description ?? $product->name,
                                "option1" => $variant->description ?? $product->name,
                                "price" => substr($variant->price, 0, -2),
                                "sku" => $product->stock_keeping_unit,
                                "inventory_management" => $source,
                                "inventory_quantity" => $variant->quantity,
                                "created_at" => $variant->created_at,
                                "updated_at" => $variant->updated_at
                            ];
                        }
                    }
                }

                $categories = $product->business_product_category_id;
                $tags = '';
                if (is_array($categories) && count($categories) > 0) {
                    foreach ($categories as $category) {
                        $tags .= $category->name . ', ';
                    }
                }

                $tempProducts = [
                    "title" => $product->name,
                    "body_html" => "<strong>" . $product->description . "</strong>",
                    "images" => $images,
                    "variants" => $variations,
                    "tags" => $tags
                ];
                array_push($prepareProducts, $tempProducts);
            }
        }

        if (count($prepareProducts) > 0) {
            $prefix = $prefix ? $prefix . '-' : null;
            $jobName = $prefix . $source . '-' . $flow;

            Log::info($logProductTemplate . '. Start sending ' . count($prepareProducts) . ' products from hitpay to ' . $source);
            $url = config('services.hotglue.api_host') . '/' . config('services.hotglue.env_id') . '/' . $flow . '/' . $business_id . '/jobs';
            $response = $this->client->post($url, [
                'body' => json_encode([
                    'job_name' => $jobName,
                    'state' => [
                        'products' => $prepareProducts
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
                    'hotglue_integration_id' => $integration_id,
                    'job_id' => $response['job_id'],
                    'job_name' => $response['job_name'],
                    'status' => $response['status'],
                    'aws_path' => $response['s3_root']
                ]);
                foreach($products as $product) {
                    $hotglueProducts = HotglueProductTracker::whereStockKeepingUnit($product->stock_keeping_unit)
                        ->whereNotNull('stock_keeping_unit')
                        ->get();

                    if ($hotglueProducts->count() === 0 && $product->variations_count > 0) {
                        foreach ($product->variations as $variant) {
                            $image = $product->images_count > 0 ? Storage::disk($storageDefaultDisk)->url($product->images[0]->path) : null;
                            HotglueProductTracker::firstOrCreate([
                                'hotglue_job_id' => $hotglueJob->id,
                                'stock_keeping_unit' => $product->stock_keeping_unit,
                                'name' => $product->name,
                                'description' => $variant->description ?? $product->name,
                                'price' => substr($variant->price, 0, -2),
                                'quantity' => $variant->quantity,
                                'image_url' => $image,
                                'published' => $product->isPublished(),
                                'manage_inventory' => $product->isManageable()
                            ]);
                        }
                    }
                }
                Log::info($logProductTemplate . '. Successfully sent ' . count($prepareProducts) . ' products from hitpay to ' . $source);
            } else {
                Log::critical($logProductTemplate . '. Failed to send ' . count($prepareProducts) . ' products from hitpay to ' . $source);
            }
        } else {
            Log::info($logProductTemplate . '. No hitpay products to send');
        }

        return true;
    }

    private function generateLogTemplate($flow, $source)
    {
        return 'Hotglue integration for ' . $source . ' ' . $flow . ' flow';
    }
}
