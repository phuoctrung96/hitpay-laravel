<?php

namespace App\Jobs\Business\Shopify\PaymentApp;

use App\BusinessShopifyStore;
use App\Services\Shopify\Webhook\ShopifyWebhookSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class RegisterPaymentWebhooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public BusinessShopifyStore $businessShopifyStore;

    /**
     * Create a new job instance.
     */
    public function __construct(BusinessShopifyStore $businessShopifyStore)
    {
        Log::info('Init RegisterPaymentWebhooks');

        $this->businessShopifyStore = $businessShopifyStore;
    }

    /**
     * Execute the job.
     *
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function handle()
    {
        Log::info('handle RegisterPaymentWebhooks');

        $shopifyWebhookSubscription = new ShopifyWebhookSubscription($this->businessShopifyStore);

        $webhooksRegistered = $shopifyWebhookSubscription->get();
        $webhooksRegistered = new Collection($webhooksRegistered['webhooks']);

        Log::info(print_r($webhooksRegistered, true));

        $webhookLists = [
            'app/uninstalled' => URL::route('api.webhook.shopify.uninstall.app')
        ];

        foreach ($webhookLists as $event => $route) {
            if ($existing = $webhooksRegistered->where('topic', $event)->first()) {
                $shopifyWebhookSubscription->delete($existing['id']);
            }

            $route = str_replace('http://', 'https://', $route);

            $createResponse = $shopifyWebhookSubscription->create([
                'topic' => $event,
                'address' => $route,
                'format' => 'json',
            ]);

            Log::info('created webhook');
            Log::info(print_r($createResponse, true));
        }
    }
}
