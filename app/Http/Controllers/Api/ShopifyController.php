<?php

namespace App\Http\Controllers\Api;

use App\Business;
use App\Business\ProductVariation;
use App\Http\Controllers\Controller;
use Exception;
use HitPay\Shopify\Shopify;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShopifyController extends Controller
{
    public function __construct()
    {
        $this->middleware('shopify.auth');
    }

    public function inventoryItemsCreated(Request $request, Business $business)
    {
        $this->tempLog(__FUNCTION__, $request, $business->getKey());
        // {
        //     "id": 271878346596884015,
        //     "sku": "example-sku",
        //     "created_at": "2019-08-08T16:17:43-04:00",
        //     "updated_at": "2019-08-08T16:17:43-04:00",
        //     "requires_shipping": true,
        //     "cost": null,
        //     "country_code_of_origin": null,
        //     "province_code_of_origin": null,
        //     "harmonized_system_code": null,
        //     "tracked": true,
        //     "country_harmonized_system_codes": [
        //     ]
        // }
    }

    public function inventoryItemsDeleted(Request $request, Business $business)
    {
        $this->tempLog(__FUNCTION__, $request, $business->getKey());
        // {
        //     "id": 271878346596884015
        // }
    }

    public function inventoryItemsUpdated(Request $request, Business $business)
    {
        $this->tempLog(__FUNCTION__, $request, $business->getKey());
        // {
        //     "id": 271878346596884015,
        //     "sku": "example-sku",
        //     "created_at": "2019-08-08T16:17:43-04:00",
        //     "updated_at": "2019-08-08T16:17:43-04:00",
        //     "requires_shipping": true,
        //     "cost": null,
        //     "country_code_of_origin": null,
        //     "province_code_of_origin": null,
        //     "harmonized_system_code": null,
        //     "tracked": true,
        //     "country_harmonized_system_codes": [
        //     ]
        // }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @throws \Exception
     */
    public function inventoryLevelsUpdated(Request $request, Business $business)
    {
        if (!$business->shopify_id || $request->get('location_id') !== $business->shopify_location_id) {
            return;
        }

        $inventory = $business->products()
            ->where('shopify_inventory_item_id', $request->get('inventory_item_id'))
            ->first();

        if (!$inventory) {
            return;
        }

        DB::beginTransaction();

        $inventory->quantity = $request->get('available');
        $inventory->save();

        DB::commit();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function productsCreated(Request $request, Business $business)
    {
        if (!$business->shopify_id) {
            return;
        }

        $productCurrency = $request->get('currency');
        $productCurrency = strtolower($productCurrency);

        if ($productCurrency !== $business->currency) {
            Log::critical('A shopify product ID : '.$request->get('id').' is created but doesn\'t sync because the'
                .' currency used is different with its business in HitPay ID : '.$business->getKey());

            return;
        }

        $shopify = new Shopify($business->shopify_domain, $business->shopify_token);

        $parent['id'] = Str::orderedUuid()->toString();
        $parent['business_id'] = $business->getKey();
        $parent['name'] = strip_tags($request->get('title'));
        $parent['description'] = trim(strip_tags($request->get('body_html')));
        $parent['currency'] = $productCurrency;
        $parent['published_at'] = ($publishedAt = $request->get('published_at'))
            ? Date::createFromTimeString($publishedAt)
            : null;
        $parent['shopify_id'] = $request->get('id');

        $i = 1;

        $variationKeyMap = [];

        foreach ($request->get('options') as $key) {
            $parent['variation_key_'.$i] = $key['name'];
            $variationKeyMap['option'.$key['position']] = 'variation_value_'.$i;

            $i++;
        }

        $variations = new Collection($request->get('variants'));

        $quantities = $shopify->inventoryLevels([
            'inventory_item_ids' => $variations->pluck('inventory_item_id')->implode(','),
            'location_ids' => $business->shopify_location_id,
        ]);

        $quantities = new Collection($quantities['inventory_levels']);

        foreach ($variations as $variation) {
            $child['id'] = Str::orderedUuid()->toString();
            $child['parent_id'] = $parent['id'];
            $child['business_id'] = $business->id;
            $child['description'] = strip_tags($variation['title']);
            $child['price'] = getRealAmountForCurrency($productCurrency, $variation['price']);

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

        $parent['shopify_data'] = $request->except('variant');
        $parent = $business->products()->create($parent);
        $parent->variations()->createMany($children);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @throws \Exception
     */
    public function productsDeleted(Request $request, Business $business)
    {
        if (!$business->shopify_id) {
            return;
        }

        $product = $business->products()->where('shopify_id', $request->get('id'))->first();

        if (!$product) {
            return;
        }

        DB::beginTransaction();

        $product->delete();

        DB::commit();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function productsUpdated(Request $request, Business $business)
    {
        if (!$business->shopify_id) {
            return;
        }

        $productCurrency = $request->get('currency');
        $productCurrency = strtolower($productCurrency);

        if ($productCurrency !== $business->currency) {
            Log::critical('A shopify product ID : '.$request->get('id').' is updated but doesn\'t sync because the'
                .' currency used is different with its business in HitPay ID : '.$business->getKey());

            return;
        }

        $existingProduct = $business->products()->where('shopify_id', $request->get('id'))->first();

        if (!$existingProduct) {
            return;
        }

        $shopify = new Shopify($business->shopify_domain, $business->shopify_token);

        $existingProduct->load('variations');

        $existingProduct->name = strip_tags($request->get('title'));
        $existingProduct->description = trim(strip_tags($request->get('body_html')));
        $existingProduct->published_at = ($publishedAt = $request->get('published_at'))
            ? Date::createFromTimeString($publishedAt)
            : null;

        $i = 1;

        $variationKeyMap = [];

        foreach ($request->get('options') as $key) {
            $parent['variation_key_'.$i] = $key['name'];
            $variationKeyMap['option'.$key['position']] = 'variation_value_'.$i;

            $i++;
        }

        $variants = collect($request->get('variants'));

        $quantities = $shopify->inventoryLevels([
            'inventory_item_ids' => $variants->pluck('inventory_item_id')->implode(','),
            'location_ids' => $business->shopify_location_id,
        ]);

        $quantities = collect($quantities['inventory_levels']);

        $variantIdsCommitted = [];

        if ($variants->count() === 1) {
            try {
                $firstVariant = $variants->first();

                $existingProduct->price = getRealAmountForCurrency($productCurrency, $firstVariant['price']);
            } catch (Exception $exception) {
                //
            }
        }

        foreach ($variants as $variant) {
            $variantIdsCommitted[] = $variant['id'];

            $existingVariant = $existingProduct->variations->where('shopify_id', $variant['id'])->first();

            if ($existingVariant instanceof ProductVariation) {
                $existingVariant->description = strip_tags($variant['title']);
                $existingVariant->price = getRealAmountForCurrency($productCurrency, $variant['price']);

                foreach ($variationKeyMap as $k => $v) {
                    $existingVariant->{$v} = $variant[$k];
                }

                $quantity = $quantities->where('inventory_item_id', $variant['inventory_item_id'])->first();

                if (isset($quantity['available'])) {
                    $existingVariant->quantity = $quantity['available'] < 0 ? 0 : $quantity['available'];
                }

                $existingVariant->shopify_sku = $variant['sku'];
                $existingVariant->save();
            } else {
                $child['id'] = Str::orderedUuid()->toString();
                $child['parent_id'] = $parent['id'];
                $child['business_id'] = $business->id;
                $child['description'] = strip_tags($variant['title']);
                $child['price'] = getRealAmountForCurrency($productCurrency, $variant['price']);

                foreach ($variationKeyMap as $key => $value) {
                    $child[$value] = $variant[$key];
                }

                $inventoryItem = $quantities->where('inventory_item_id', $variant['inventory_item_id'])->first();

                if (isset($inventoryItem['available']) && is_int($inventoryItem['available'])) {
                    $child['quantity'] = $inventoryItem['available'] < 0 ? 0 : $inventoryItem['available'];
                }

                $child['shopify_id'] = $variant['id'];
                $child['shopify_inventory_item_id'] = $variant['inventory_item_id'];
                $child['shopify_stock_keeping_unit'] = $variant['sku'];
                $child['shopify_data'] = $variant;

                if (is_null($existingProduct->price)) {
                    $existingProduct->price = $child['price'];
                }

                $existingProduct->variation()->create($child);

                unset($child);
            }
        }

        foreach ($existingProduct->variation->whereNotIn('shopify_id', $variantIdsCommitted) as $item) {
            /**
             * @var \App\Business\ProductVariation $item
             */
            $item->forceDelete();
        }

        $existingProduct->save();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $id
     */
    public function locationsDeleted(Request $request, Business $business)
    {
        if (!$business->shopify_id) {
            return;
        }

        if ($request->get('id') === $business->shopify_location_id) {
            Log::critical('The location for Business ID: '.$business->getKey().' Shopify ID: '.$business->shopify_id
                .' is deleted and products resynchronization is required.');
        }
    }

    public function redactShop(Request $request)
    {
        $this->tempLog(__FUNCTION__, $request);
    }

    public function shopUpdated(Request $request, Business $business)
    {
        try {
            $shopifyCurrency = $request->get('currency');

            if (!$shopifyCurrency) {
                return;
            }

            $shopifyCurrency = strtolower($shopifyCurrency);

            if ($shopifyCurrency !== $business->currency) {
                Log::critical('The currency for Business ID: '.$business->getKey().' Shopify ID: '.$business->shopify_id
                    .' is updated and doesn\'t meet our requirement.');
            }
        } catch (Exception $exception) {
            $this->tempLog(__FUNCTION__, $request, $business->getKey());

            Log::error(get_class($exception).' @ '.$exception->getFile().':'.$exception->getLine().' => '
                .$exception->getMessage());
        }
    }

    public function appSubscriptionUpdated(Request $request, Business $business)
    {
        $this->tempLog(__FUNCTION__, $request, $business->getKey());
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @throws \Exception
     */
    public function uninstalled(Request $request, Business $business)
    {
        if ($business->shopify_id !== $request->get('id')) {
            return;
        }

        $business->products()->whereNotNull('shopify_id')->delete();

        $business->shopify_id = null;
        $business->shopify_domain = null;
        $business->shopify_token = null;
        $business->shopify_location_id = null;
        $business->shopify_data = null;

        $business->save();
    }

    private function tempLog(string $function, Request $request, string $id = null)
    {
        $data['function'] = __CLASS__.'@'.$function;
        $data['headers'] = $request->header();
        $data['method'] = $request->method();
        $data['requests'] = $request->all();
        $data['id'] = $id;

        Storage::append('log/webhook.log', json_encode($data, JSON_PRETTY_PRINT)."\n\n");
    }
}
