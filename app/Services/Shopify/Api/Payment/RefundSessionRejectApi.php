<?php

namespace App\Services\Shopify\Api\Payment;

use App\Services\Shopify\Api\ApiService;
use GuzzleHttp\Client;

class RefundSessionRejectApi extends ApiService
{
    protected $id = "";
    protected $reason = "";

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function handle() : array
    {
        $params = [
            'query' => $this->getQueryString(),
            'variables' => [
                'id' => $this->id,
                'reason' => $this->reason,
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

        $refundSessionError = $arrResponse['data']['refundSessionReject']['userErrors'];

        if (is_array($refundSessionError)) {
            if (count($refundSessionError) > 0) {
                throw new \Exception($refundSessionError[0]['message'], 500);
            }
        }

        return $arrResponse;
    }

    private function getQueryString()
    {
        $queryString = <<<QUERY
mutation RefundSessionReject(\$id: ID!, \$reason: RefundSessionRejectionReasonInput!) {
  refundSessionReject(id: \$id, reason: \$reason) {
    refundSession {
      id
      status {
        code
        reason {
          ... on RefundSessionStatusReason {
            code
            merchantMessage
          }
        }
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
