<?php

namespace App\Services\Shopify\Api\Payment;

use App\Services\Shopify\Api\ApiService;
use GuzzleHttp\Client;

class PaymentsAppConfigureApi extends ApiService
{
    protected bool $ready = false;
    protected string $externalHandler = "Hitpay Payment v2";

    /**
     * @param bool $ready
     * @return $this
     */
    public function setReady(bool $ready) : self
    {
        $this->ready = $ready;

        return $this;
    }

    /***
     * @param string $externalHandler
     * @return $this
     */
    public function setExternalHandler(string $externalHandler) : self
    {
        $this->externalHandler = strval($externalHandler);

        return $this;
    }

    /***
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        $params = [
            'query' => $this->getQueryString(),
            'variables' => [
                'ready' => $this->ready,
                'externalHandle' => $this->externalHandler
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

        $paymentsAppConfuguration = $arrResponse['data']['paymentsAppConfigure']['paymentsAppConfiguration'];

        if (!$paymentsAppConfuguration) {
            $userErrors = $arrResponse['data']['paymentsAppConfigure']['userErrors'];

            $errorMessage = "";

            if (!is_array($userErrors)) {
                $errorMessage .= "Undefined errors. Error message is not on array!";
            }

            $errorMessage .= json_encode($userErrors);

            throw new \Exception("Payment App Configuration is empty with error: " . $errorMessage);
        }

        $responseDataReady = $arrResponse['data']['paymentsAppConfigure']['paymentsAppConfiguration']['ready'];

        return $responseDataReady;
    }

    /***
     * @return string
     */
    private function getQueryString() : string
    {
        $queryString = <<<QUERY
mutation paymentsAppConfigure(\$externalHandle: String, \$ready: Boolean!) {
    paymentsAppConfigure(externalHandle: \$externalHandle, ready: \$ready) {
        paymentsAppConfiguration {
          externalHandle
          ready
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
