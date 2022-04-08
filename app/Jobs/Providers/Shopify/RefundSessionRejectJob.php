<?php

namespace App\Jobs\Providers\Shopify;

use App\Services\Shopify\Api\Payment\RefundSessionRejectApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefundSessionRejectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $shopifyToken;
    protected $shopifyDomain;
    protected $shopifyApiVersion;
    protected $shopifyGid;
    protected $shopifyReason;

    /**
     * Create a new job instance.
     */
    public function __construct(array $paramsJob)
    {
        $this->shopifyToken = $paramsJob['shopifyToken'];
        $this->shopifyDomain = $paramsJob['shopifyDomain'];
        $this->shopifyApiVersion = $paramsJob['shopifyApiVersion'];
        $this->shopifyGid = $paramsJob['shopifyGid'];
        $this->shopifyReason = $paramsJob['shopifyReason'];
    }

    /**
     * Execute the job.
     */
    public function handle() : void
    {
        try {
            sleep(5);

            // call refund session reject api
            $refundSessionReject = new RefundSessionRejectApi();
            $refundSessionReject->setToken($this->shopifyToken);

            $url = "https://".$this->shopifyDomain."/payments_apps/api/".$this->shopifyApiVersion."/graphql.json";
            $refundSessionReject->setUrl($url);
            $refundSessionReject->setId($this->shopifyGid);
            $refundSessionReject->setReason($this->shopifyReason);
            $response = $refundSessionReject->handle();
        } catch (\Exception $e) {
            Log::info(__CLASS__ . ' --------------- end error: ' . $e->getMessage());
        }
    }
}
