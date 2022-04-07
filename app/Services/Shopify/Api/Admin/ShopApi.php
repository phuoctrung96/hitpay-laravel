<?php

namespace App\Services\Shopify\Api\Admin;

use App\Services\Shopify\Api\ApiService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ShopApi extends ApiService
{
    public function handle()
    {
        $params = [
            'query' => $this->getQueryString(),
        ];

        try {
            $client = new Client();

            $response = $client->post($this->getUrl(), [
                'body' => json_encode($params),
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Shopify-Access-Token' => $this->getToken()
                ],
                'verify' => false
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    private function getQueryString()
    {
        $queryString = <<<'GRAPHQL'
    {
        shop {
            name
        }
    }
GRAPHQL;
        return $queryString;
    }
}
