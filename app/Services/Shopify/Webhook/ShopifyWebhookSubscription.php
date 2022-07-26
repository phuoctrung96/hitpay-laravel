<?php

namespace App\Services\Shopify\Webhook;

use App\BusinessShopifyStore;
use App\Exceptions\HitPayLogicException;
use GuzzleHttp\Client;

class ShopifyWebhookSubscription
{
    protected BusinessShopifyStore $businessShopifyStore;

    protected string $version = '2021-10';

    /**
     * @param BusinessShopifyStore $businessShopifyStore
     */
    public function __construct(BusinessShopifyStore $businessShopifyStore)
    {
        $this->businessShopifyStore = $businessShopifyStore;
    }

    /**
     * @param array|null $body
     * @param array|null $parameters
     * @return array
     * @throws HitPayLogicException
     */
    public function create(array $body = null, array $parameters = null): array
    {
        // https://shopify.dev/api/admin-rest/2022-04/resources/webhook#post-webhooks
        if ($body !== null) {
            $body['webhook'] = $body;
        }

        return $this->client('post', 'webhooks', $parameters, $body);
    }

    /**
     * @param array|null $parameters
     * @return array
     * @throws HitPayLogicException
     */
    public function get(array $parameters = null): array
    {
        // https://shopify.dev/api/admin-rest/2022-04/resources/webhook#get-webhooks
        return $this->client('get', 'webhooks', $parameters);
    }

    /**
     * @param string $webhookId
     * @return array
     * @throws HitPayLogicException
     */
    public function delete(string $webhookId): array
    {
        return $this->client('delete', 'webhooks/'.$webhookId);
    }

    /**
     * @param string $method
     * @param string $topic
     * @param array|null $parameters
     * @param array|null $body
     * @return array
     * @throws HitPayLogicException
     */
    private function client(string $method, string $topic = 'webhooks', array $parameters = null, array $body = null): array
    {
        $client = new Client;

        $url = 'https://'.$this->businessShopifyStore->shopify_domain.'/admin/api/'.$this->version.'/'.$topic.'.json';

        if ($parameters !== null) {
            $url .= '?'.http_build_query($parameters, '', '&');
        }

        $options['headers'] = [
            'X-Shopify-Access-Token' => $this->businessShopifyStore->shopify_token,
        ];

        if ($body !== null) {
            $options['json'] = $body;
        }

        if ($method === 'get') {
            $response = $client->get($url, $options);
        } elseif ($method === 'post') {
            $response = $client->post($url, $options);
        } elseif ($method === 'put') {
            $response = $client->put($url, $options);
        } elseif ($method === 'delete') {
            $response = $client->delete($url, $options);
        } else {
            throw new HitPayLogicException('The given method is invalid.');
        }

        $response = json_decode($response->getBody()->getContents(), true);

        if ($response === false) {
            throw new HitPayLogicException('Error while decoding response from Shopify webhook subscription: '.json_last_error_msg());
        }

        return $response;
    }
}
