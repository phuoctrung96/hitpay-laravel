<?php

namespace App\Services\Shopify\Api\Payment;

use App\Services\Shopify\Api\ApiService;
use GuzzleHttp\Client;

class PaymentSessionResolveApi extends ApiService
{
    protected $id = "";
    protected $authorizationExpiresAt = "";

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setAuthorizationExpiresAt($authorizationExpiresAt)
    {
        $this->authorizationExpiresAt = $authorizationExpiresAt;
    }

    public function handle()
    {
        $params = [
            'query' => $this->getQueryString(),
            'variables' => [
                'id' => $this->id,
                'authorizationExpiresAt' => $this->authorizationExpiresAt
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

        return json_decode($response->getBody(), true);
    }

    private function getQueryString()
    {
        $queryString = <<<QUERY
mutation PaymentSessionResolve(\$id: ID!, \$authorizationExpiresAt: DateTime) {
  paymentSessionResolve(id: \$id, authorizationExpiresAt: \$authorizationExpiresAt) {
    paymentSession {
      id
      status {
        code
      }
      nextAction {
        action
        context {
          ... on PaymentSessionActionsRedirect {
            redirectUrl
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
