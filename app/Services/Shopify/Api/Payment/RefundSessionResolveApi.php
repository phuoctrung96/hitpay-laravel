<?php

namespace App\Services\Shopify\Api\Payment;

use App\Services\Shopify\Api\ApiService;
use GuzzleHttp\Client;

class RefundSessionResolveApi extends ApiService
{
    protected $id = "";

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $params = [
            'query' => $this->getQueryString(),
            'variables' => [
                'id' => $this->id
            ]
        ];

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

        $arrResponse = json_decode($response->getBody(), true);

        if (!is_array($arrResponse)) {
            throw new \Exception("Shopify response not in array response");
        }

        if (!array_key_exists('data', $arrResponse)) {
            throw new \Exception("Shopify response not have result response");
        }

        $refundSession = $arrResponse['data']['refundSessionResolve']['refundSession'];
        $refundSessionError = $arrResponse['data']['refundSessionResolve']['userErrors'];

        if (is_array($refundSessionError)) {
            if (count($refundSessionError) > 0) {
                throw new \Exception($refundSessionError[0]['message'], 500);
            }
        }

        return $refundSession;
    }

    private function getQueryString()
    {
        $queryString = <<<QUERY
mutation RefundSessionResolve(\$id: ID!) {
  refundSessionResolve(id: \$id) {
    refundSession {
      id
      status {
        code
      }
    }
    userErrors {
      field
      message
    }
  }
}
QUERY;

        return $queryString;
    }
}
