<?php

namespace App\Jobs\Business\Shopify;

use App\Business;
use HitPay\Shopify\Shopify;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $business;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Business $business)
    {
        $this->business = $business;
    }

    /**
     * Execute the job.
     *
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function handle() : void
    {
        if (!$this->business->shopify_id || !$this->business->shopify_location_id) {
            return;
        }

        $shopify = new Shopify($this->business->shopify_domain, $this->business->shopify_token);
        $shopifyShop = $shopify->shop()['shop'];

        $products = new Collection;

        $keepGoing = true;
        $sinceId = null;
        $config['limit'] = 250;

        do {
            if (!is_null($sinceId)) {
                $config['since_id'] = $sinceId;
            }

            $response = $shopify->products($config);

            if (count($response['products']) < $config['limit']) {
                $keepGoing = false;
            }

            foreach ($response['products'] as $product) {
                $sinceId = $product['id'];

                $products->add($product);
            }

            usleep(500000);
        } while ($keepGoing);

        Cache::put('business_'.$this->business->getKey().'_shopify_syncing', $products->count());

        $failed = [];

        $currency = strtolower($shopifyShop['currency']);

        foreach ($products as $product) {
            try {
                $parent['id'] = Str::orderedUuid()->toString();
                $parent['business_id'] = $this->business->getKey();
                $parent['name'] = strip_tags($product['title']);
                $parent['description'] = trim(strip_tags($product['body_html']));
                $parent['currency'] = $currency;

                $parent['published_at'] = isset($product['published_at']) && $product['published_at']
                    ? Date::createFromTimeString($product['published_at'])
                    : null;

                $parent['shopify_id'] = $product['id'];

                $i = 1;

                $variationKeyMap = [];

                foreach ($product['options'] as $key) {
                    $parent['variation_key_'.$i] = $key['name'];
                    $variationKeyMap['option'.$key['position']] = 'variation_value_'.$i;

                    $i++;
                }

                $variations = new Collection($product['variants']);

                $quantities = $shopify->inventoryLevels([
                    'inventory_item_ids' => $variations->pluck('inventory_item_id')->implode(','),
                    'location_ids' => $this->business->shopify_location_id,
                ]);

                $quantities = new Collection($quantities['inventory_levels']);

                foreach ($variations as $variation) {
                    $child['id'] = Str::orderedUuid()->toString();
                    $child['parent_id'] = $parent['id'];
                    $child['business_id'] = $this->business->id;
                    $child['description'] = strip_tags($variation['title']);
                    $child['price'] = getRealAmountForCurrency($currency, $variation['price']);

                    foreach ($variationKeyMap as $key => $value) {
                        $child[$value] = $variation[$key];
                    }

                    $inventoryItem = $quantities->where('inventory_item_id', $variation['inventory_item_id'])->first();

                    if (isset($inventoryItem['available'])) {
                        $child['quantity'] = $inventoryItem['available'] < 0 ? 0 : $inventoryItem['available'];
                    }

                    $child['shopify_id'] = $variation['id'];
                    $child['shopify_inventory_item_id'] = $variation['inventory_item_id'];
                    $child['shopify_stock_keeping_unit'] = $variation['sku'];
                    $child['shopify_data'] = $variation;

                    $children[] = $child;

                    unset($child);
                }

                unset($product['variants']);

                $parent['shopify_data'] = $product;

                $parent = $this->business->products()->create($parent);

                $parent->variations()->createMany($children);
            } catch (QueryException $exception) {
                Log::error(get_class($exception).' @ '.$exception->getFile().':'.$exception->getLine().' => '
                    .$exception->getMessage());

                $failed[] = $product['id'] ?? 'unknown';
            }

            unset($parent, $children);

            usleep(500000);

            Cache::increment('business_'.$this->business->getKey().'_shopify_synced');
        }

        Cache::forget('business_'.$this->business->getKey().'_shopify_syncing');
        Cache::forget('business_'.$this->business->getKey().'_shopify_synced');
    }
}
