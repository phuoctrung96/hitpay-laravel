<?php

namespace App\Jobs\Providers\Shopify;

use App\Business;
use App\BusinessShopifyRefund;
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

    protected string $shopifyToken;
    protected string $shopifyDomain;
    protected string $shopifyApiVersion;
    protected string $shopifyGid;
    protected string $businessId;
    protected string $paymentId;
    protected string $refundId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $paramsJob)
    {
        $this->shopifyToken = $paramsJob['shopifyToken'];
        $this->shopifyDomain = $paramsJob['shopifyDomain'];
        $this->shopifyApiVersion = $paramsJob['shopifyApiVersion'];
        $this->shopifyGid = $paramsJob['shopifyGid'];
        $this->businessId = $paramsJob['business_id'];
        $this->paymentId = $paramsJob['payment_id'];
        $this->refundId = $paramsJob['refund_id'];
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
            $responseApi = $refundSessionResolve->handle();

            $business = Business::find($this->businessId);

            if (!$business instanceof Business) {
                Log::critical("Business not found on refund session reject job with ID {$this->businessId}");
            } else {
                $businessShopifyRefund = $business->shopifyRefunds()->where('payment_id', $this->paymentId)
                    ->where('refund_id', $this->refundId)->first();

                if (!$businessShopifyRefund instanceof BusinessShopifyRefund) {
                    Log::critical("businessShopifyRefund not found on refund session reject job with
                        Business ID {$this->businessId}, Refund ID {$this->refundId}, Payment ID {$this->paymentId}");
                } else {
                    $businessShopifyRefund->response_data = $responseApi;
                    $businessShopifyRefund->save();
                }
            }
        } catch (\Exception $e) {
            Log::critical("Error on refund session resolve job with message: {$e->getMessage()}
                with error: {$e->getTraceAsString()}");
        }
    }
}
