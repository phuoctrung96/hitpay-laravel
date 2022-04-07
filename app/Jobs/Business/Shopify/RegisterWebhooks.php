<?php

namespace App\Jobs\Business\Shopify;

use App\Business;
use HitPay\Shopify\Shopify;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class RegisterWebhooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $business;

    /**
     * Create a new job instance.
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
    public function handle()
    {
        $shopify = new Shopify($this->business->shopify_domain, $this->business->shopify_token);

        $webhooksRegistered = $shopify->webhooks();
        $webhooksRegistered = new Collection($webhooksRegistered['webhooks']);

        $businessId = $this->business->id;

        foreach ([
            'app/uninstalled' => URL::route('api.webhook.shopify.uninstalled', $businessId),
            'inventory_items/create' => URL::route('api.webhook.shopify.inventory-items.created', $businessId),
            'inventory_items/delete' => URL::route('api.webhook.shopify.inventory-items.deleted', $businessId),
            'inventory_items/update' => URL::route('api.webhook.shopify.inventory-items.updated', $businessId),
            'inventory_levels/update' => URL::route('api.webhook.shopify.inventory-levels.updated', $businessId),
            'locations/delete' => URL::route('api.webhook.shopify.locations.deleted', $businessId),
            'products/create' => URL::route('api.webhook.shopify.products.created', $businessId),
            'products/delete' => URL::route('api.webhook.shopify.products.deleted', $businessId),
            'products/update' => URL::route('api.webhook.shopify.products.updated', $businessId),
            'shop/update' => URL::route('api.webhook.shopify.shop.updated', $businessId),
        ] as $event => $route) {
            if ($existing = $webhooksRegistered->where('topic', $event)->first()) {
                $shopify->deleteWebhook($existing['id']);
            }

            $route = str_replace('http://', 'https://', $route);

            $shopify->createWebhook([
                'topic' => $event,
                'address' => $route,
                'format' => 'json',
            ]);
        }
    }
}
