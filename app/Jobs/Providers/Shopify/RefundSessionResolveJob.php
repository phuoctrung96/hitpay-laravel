<?php

namespace App\Jobs\Providers\Shopify;

use App\Services\Shopify\Api\Payment\RefundSessionResolveApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefundSessionResolveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $shopifyToken;
    protected $shopifyDomain;
    protected $shopifyApiVersion;
    protected $shopifyGid;

    /**
     * Create a new job instance.
     */
    public function __construct(array $paramsJob)
    {
        $this->shopifyToken = $paramsJob['shopifyToken'];
        $this->shopifyDomain = $paramsJob['shopifyDomain'];
        $this->shopifyApiVersion = $paramsJob['shopifyApiVersion'];
        $this->shopifyGid = $paramsJob['shopifyGid'];
    }

    /**
     * Execute the job.
     */
    public function handle() : void
    {
        try {
            sleep(5);
            // call shopify refund resolve
            $refundSessionResolve = new RefundSessionResolveApi();
            $refundSessionResolve->setToken($this->shopifyToken);

            $url = "https://".$this->shopifyDomain."/payments_apps/api/".$this->shopifyApiVersion."/graphql.json";
            $refundSessionResolve->setUrl($url);
            $refundSessionResolve->setId($this->shopifyGid);
            $shopifyResponse = $refundSessionResolve->handle();
        } catch (\Exception $e) {
            Log::info(__CLASS__ . ' --------------- end error: ' . $e->getMessage());
        }
    }
}
