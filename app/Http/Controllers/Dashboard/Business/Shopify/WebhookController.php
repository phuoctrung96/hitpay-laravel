<?php

namespace App\Http\Controllers\Dashboard\Business\Shopify;

use App\Enumerations\CurrencyCode;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    /**
     * WebhookController constructor.
     */
    public function __construct()
    {
        $this->middleware('shopify.authentication');
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondOkay(Request $request)
    {
        return Response::json();
    }

    // KIV - Check request first.
    public function inventoryItemsCreated(Request $request, string $id)
    {
        // TODO KIV
        $this->tempLog(__FUNCTION__, $request, $id);
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

    // KIV - Check request first.
    public function inventoryItemsDeleted(Request $request, string $id)
    {
        // TODO KIV
        $this->tempLog(__FUNCTION__, $request, $id);
        // {
        //     "id": 271878346596884015
        // }
    }

    // KIV - Check request first.
    public function inventoryItemsUpdated(Request $request, string $id)
    {
        // TODO KIV
        $this->tempLog(__FUNCTION__, $request, $id);
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

    // KIV - Check request first.
    public function inventoryLevelsUpdated(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user || !$user->shopify_id || $request->get('location_id') !== $user->shopify_location_id) {
            return;
        }

        $inventory = $user->product()->where('shopify_inventory_item_id', $request->get('inventory_item_id'))->first();

        if (!$inventory) {
            return;
        }

        $inventory->quantity = $request->get('available');
        $inventory->save();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $id
     *
     * @throws \ReflectionException
     */
    public function productsCreated(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user || !$user->shopify_id) {
            return;
        }

        /** @var \BNMetrics\Shopify\Facade\ShopifyFacade $shopifyAccount */
        $shopifyAccount = Shopify::retrieve($user->shopify_domain, $user->shopify_token);
        $zeroDecimal = CurrencyCode::isZeroDecimal($user->default_currency_code);

        $parent['id'] = Str::orderedUuid()->toString();
        $parent['name'] = strip_tags($request->get('title'));
        $parent['remark'] = trim(strip_tags($request->get('body_html')));
        $parent['weight'] = null;
        $parent['currency_code'] = $user->default_currency_code;
        $parent['is_pickup_allowed'] = false;
        $parent['activated_at'] = \Carbon\Carbon::createFromTimeString($request->get('published_at'));
        $parent['has_variations'] = true;
        $parent['shopify_id'] = $request->get('id');

        if (($image = $request->get('image')) && isset($image['src'])) {
            $parent['shopify_image_url'] = $image['src'];
        }

        $i = 1;

        $variationKeyMap = [];

        foreach ($request->get('options') as $key) {
            $productVariation = 'variant_'.$i++;
            $parent[$productVariation.'_key'] = $key['name'];
            $variationKeyMap['option'.$key['position']] = $productVariation.'_value';
        }

        $variants = collect($request->get('variants'));
        $quantities = $shopifyAccount->get('inventory_levels', [
            'inventory_item_ids' => $variants->pluck('inventory_item_id')->implode(','),
            'location_ids' => $user->shopify_location_id,
        ]);
        $quantities = collect($quantities['inventory_levels']);

        foreach ($variants as $variant) {
            $child['id'] = Str::orderedUuid()->toString();
            $child['account_id'] = $user->id;
            $child['name'] = $parent['name'].' ('.strip_tags($variant['title']).')';
            $child['currency_code'] = $parent['currency_code'];
            $child['amount'] = (int) ($zeroDecimal ? $variant['price'] : $variant['price'] * 100);

            if (!isset($parent['amount'])) {
                $parent['amount'] = $child['amount'];
            }

            $child['is_manageable'] = $variant['inventory_management'] ? true : false;

            if (!isset($parent['is_manageable']) || $parent['is_manageable'] !== true) {
                $parent['is_manageable'] = $child['is_manageable'];
            }

            $child['is_physical'] = $variant['requires_shipping'];

            if (!isset($parent['is_physical']) || $parent['is_physical'] !== true) {
                $parent['is_physical'] = $child['is_physical'];
            }

            if (!isset($parent['is_delivery_available']) || $parent['is_delivery_available'] !== true) {
                $parent['is_delivery_available'] = $child['is_physical'];
            }

            foreach ($variationKeyMap as $k => $v) {
                $child[$v] = $variant[$k];
            }

            $child['quantity'] = $child['is_manageable']
                ? $quantities->where('inventory_item_id', $variant['inventory_item_id'])->first()['available']
                : null;

            if ($child['quantity'] < 0) {
                $child['quantity'] = 0;
            }

            $child['activated_at'] = $parent['activated_at'];
            $child['shopify_id'] = $variant['id'];
            $child['shopify_inventory_item_id'] = $variant['inventory_item_id'];
            $child['shopify_sku'] = $variant['sku'];

            $children[] = $child;

            unset($child);
        }

        /** @var \App\Product $product */
        $user->product()->create($parent)->variation()->createMany($children);
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @throws \Exception
     */
    public function productsDeleted(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user || !$user->shopify_id) {
            return;
        }

        DB::beginTransaction();

        $product = $user->product()->with('variation')->where('shopify_id', $request->get('id'))->first();

        if (!$product) {
            return;
        }

        foreach ($product->variation as $variation) {
            $variation->forceDelete();
        }

        $product->forceDelete();

        DB::commit();
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @throws \ReflectionException
     */
    public function productsUpdated(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user || !$user->shopify_id) {
            return;
        }

        $existingProduct = $user->product()->where('shopify_id', $request->get('id'))->first();

        if (!$existingProduct) {
            return;
        }

        $existingProduct->load('account', 'variation');

        $user = $existingProduct->account;
        $shopifyAccount = $shopifyAccount = Shopify::retrieve($user->shopify_domain, $user->shopify_token);
        $zeroDecimal = CurrencyCode::isZeroDecimal($user->default_currency_code);

        $existingProduct->name = strip_tags($request->get('title'));
        $existingProduct->remark = trim(strip_tags($request->get('body_html')));
        $existingProduct->activated_at = $request->get('published_at')
            ? \Carbon\Carbon::createFromTimeString($request->get('published_at'))
            : null;

        if (($image = $request->get('image')) && isset($image['src'])) {
            $existingProduct->shopify_image_url = $image['src'];
        }

        $i = 1;

        $variationKeyMap = [];

        foreach ($request->get('options') as $key) {
            $productVariation = 'variant_'.$i++;
            $parent[$productVariation.'_key'] = $key['name'];
            $variationKeyMap['option'.$key['position']] = $productVariation.'_value';
        }

        $variants = collect($request->get('variants'));
        $quantities = $shopifyAccount->get('inventory_levels', [
            'inventory_item_ids' => $variants->pluck('inventory_item_id')->implode(','),
            'location_ids' => $user->shopify_location_id,
        ]);
        $quantities = collect($quantities['inventory_levels']);

        $variantIdsCommitted = [];

        if ($variants->count() === 1) {
            try {
                $firstPrice = $variants->first();

                $existingProduct->amount = (int) ($zeroDecimal ? $firstPrice['price'] : $firstPrice['price'] * 100);
            } catch (\Exception $exception) {
                // todo this is temp fix without testing
            }
        }

        foreach ($variants as $variant) {
            $variantIdsCommitted[] = $variant['id'];

            $existingVariant = $existingProduct->variation->where('shopify_id', $variant['id'])->first();

            if ($existingVariant instanceof Variation) {
                $existingVariant->name = $existingProduct->name.' ('.strip_tags($variant['title']).')';
                $existingVariant->amount = (int) ($zeroDecimal ? $variant['price'] : $variant['price'] * 100);
                $existingVariant->is_manageable = $variant['inventory_management'] ? true : false;
                $existingVariant->is_physical = $variant['requires_shipping'];
                foreach ($variationKeyMap as $k => $v) {
                    $existingVariant->{$v} = $variant[$k];
                }
                $existingVariant->quantity = $existingVariant->is_manageable
                    ? $quantities->where('inventory_item_id', $variant['inventory_item_id'])->first()['available']
                    : null;

                if ($existingVariant->quantity < 0) {
                    $existingVariant->quantity = 0;
                }

                $existingVariant->activated_at = $existingProduct->activated_at;
                $existingVariant->shopify_sku = $variant['sku'];
                $existingVariant->save();
            } else {
                $child['id'] = Str::orderedUuid()->toString();
                $child['account_id'] = $user->id;
                $child['name'] = $existingProduct->name.' ('.strip_tags($variant['title']).')';
                $child['amount'] = (int) ($zeroDecimal ? $variant['price'] : $variant['price'] * 100);

                if (is_null($existingProduct->amount)) {
                    $existingProduct->amount = $child['amount'];
                }

                $child['is_manageable'] = $variant['inventory_management'] ? true : false;

                if (is_null($existingProduct->is_manageable) || $existingProduct->is_manageable !== true) {
                    $existingProduct->is_manageable = $child['is_manageable'];
                }

                $child['is_physical'] = $variant['requires_shipping'];

                if (is_null($existingProduct->is_physical) || $existingProduct->is_physical !== true) {
                    $existingProduct->is_physical = $child['is_physical'];
                }

                if (is_null($existingProduct->is_delivery_available)
                    || $existingProduct->is_delivery_available !== true) {
                    $existingProduct->is_delivery_available = $child['is_physical'];
                }

                foreach ($variationKeyMap as $k => $v) {
                    $child[$v] = $variant[$k];
                }

                $child['quantity'] = $child['is_manageable']
                    ? $quantities->where('inventory_item_id', $variant['inventory_item_id'])->first()['available']
                    : null;

                if ($child['quantity'] < 0) {
                    $child['quantity'] = 0;
                }

                $child['activated_at'] = $existingProduct->activated_at;
                $child['shopify_id'] = $variant['id'];
                $child['shopify_inventory_item_id'] = $variant['inventory_item_id'];
                $child['shopify_sku'] = $variant['sku'];

                $existingProduct->variation()->create($child);

                unset($child);
            }
        }

        foreach ($existingProduct->variation->whereNotIn('shopify_id', $variantIdsCommitted) as $item) {
            /** @var \App\Variation $item */
            $item->forceDelete();
        }

        $existingProduct->save();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $id
     */
    public function locationsDeleted(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user || !$user->shopify_id || $request->get('id') !== $user->shopify_location_id) {
            return;
        }
        // TODO location gone please do something.
    }

    /**
     * @param \Illuminate\Http\Request $request
     */
    public function redactShop(Request $request)
    {
        // TODO KIV - Check if data cleared
        $this->tempLog(__FUNCTION__, $request);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $id
     */
    public function storeUpdated(Request $request, string $id)
    {
        // TODO KIV - Check the store currency change.
        $this->tempLog(__FUNCTION__, $request, $id);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $id
     */
    public function appSubscriptionUpdated(Request $request, string $id)
    {
        // TODO KIV - This is weird, the topic is missing.
        $this->tempLog(__FUNCTION__, $request, $id);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $id
     */
    public function uninstall(Request $request, string $id)
    {
        $user = User::where('shopify_id', $request->get('id'))->first();

        $user->product()->whereNotNull('shopify_id')->forceDelete();

        $subscriptions = $user->shopifySubscriptions()->get();

        foreach ($subscriptions as $subscription) {
            $subscription->delete();
        }

        $extraData = $user->extra_data;

        $extraData['shopify_logs']['store'][] = [
            'id' => $user->shopify_id,
            'domain' => $user->shopify_domain,
            'logged_at' => Carbon::now()->toAtomString(),
        ];

        $user->extra_data = $extraData;
        $user->shopify_id = null;
        $user->shopify_domain = null;
        $user->shopify_token = null;
        $user->shopify_location_id = null;

        $user->save();

        // handle uninstall, e.g. set subscription to null.
        // {
        //     "id": 690933842,
        //     "name": "Super Toys",
        //     "email": "super@supertoys.com",
        //     "domain": null,
        //     "province": "Tennessee",
        //     "country": "US",
        //     "address1": "190 MacLaren Street",
        //     "zip": "37178",
        //     "city": "Houston",
        //     "source": null,
        //     "phone": "3213213210",
        //     "latitude": null,
        //     "longitude": null,
        //     "primary_locale": "en",
        //     "address2": null,
        //     "created_at": null,
        //     "updated_at": null,
        //     "country_code": "US",
        //     "country_name": "United States",
        //     "currency": "USD",
        //     "customer_email": "super@supertoys.com",
        //     "timezone": "(GMT-05:00) Eastern Time (US & Canada)",
        //     "iana_timezone": null,
        //     "shop_owner": "Steve Jobs",
        //     "money_format": "$",
        //     "money_with_currency_format": "$ USD",
        //     "weight_unit": "kg",
        //     "province_code": "TN",
        //     "taxes_included": null,
        //     "tax_shipping": null,
        //     "county_taxes": null,
        //     "plan_display_name": "Shopify Plus",
        //     "plan_name": "enterprise",
        //     "has_discounts": true,
        //     "has_gift_cards": true,
        //     "myshopify_domain": null,
        //     "google_apps_domain": null,
        //     "google_apps_login_enabled": null,
        //     "money_in_emails_format": "$",
        //     "money_with_currency_in_emails_format": "$ USD",
        //     "eligible_for_payments": true,
        //     "requires_extra_payments_agreement": false,
        //     "password_enabled": null,
        //     "has_storefront": true,
        //     "eligible_for_card_reader_giveaway": false,
        //     "finances": true,
        //     "primary_location_id": 905684977,
        //     "checkout_api_supported": true,
        //     "multi_location_enabled": false,
        //     "setup_required": false,
        //     "force_ssl": false,
        //     "pre_launch_enabled": false,
        //     "enabled_presentment_currencies": [
        //         "USD"
        //     ]
        // }
    }

    // Helper function for logging only.
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
