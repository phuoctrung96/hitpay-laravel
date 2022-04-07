<?php

namespace HitPay\Shopify;

use App\Exceptions\HitPayLogicException;
use GuzzleHttp\Client;

class Shopify
{
    public $domain;

    public $token;

    public $version = '2020-01';

    /**
     * Shopify constructor.
     */
    public function __construct(string $domain, string $token)
    {
        $this->domain = $domain;
        $this->token = $token;
    }

    /**
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function shop()
    {
        return $this->client('get', 'shop');
    }

    /**
     * @param array|null $parameters
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function locations(array $parameters = null)
    {
        return $this->client('get', 'locations', $parameters);
    }

    /**
     * @param array|null $parameters
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function products(array $parameters = null)
    {
        return $this->client('get', 'products', $parameters);
    }

    /**
     * @param array|null $parameters
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function inventoryLevels(array $parameters = null)
    {
        return $this->client('get', 'inventory_levels', $parameters);
    }

    /**
     * @param array|null $parameters
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function adjustInventoryLevels(array $parameters = null)
    {
        return $this->client('post', 'inventory_levels/adjust', $parameters);
    }

    /**
     * @param array|null $parameters
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function countProducts(array $parameters = null)
    {
        return $this->client('get', 'products/count', $parameters);
    }

    /**
     * @param array|null $parameters
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function webhooks(array $parameters = null)
    {
        return $this->client('get', 'webhooks', $parameters);
    }

    /**
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function uninstall()
    {
        return $this->client('delete', 'api_permissions/current');
    }

    /**
     * @param array|null $body
     * @param array|null $parameters
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function createWebhook(array $body = null, array $parameters = null)
    {
        if ($body !== null) {
            $body['webhook'] = $body;
        }

        return $this->client('post', 'webhooks', $parameters, $body);
    }

    /**
     * @param string $id
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function deleteWebhook(string $id)
    {
        return $this->client('delete', 'webhooks/'.$id);
    }

    /**
     * @param string $method
     * @param string $topic
     * @param array|null $parameters
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \App\Exceptions\HitPayLogicException
     */
    private function client(string $method, string $topic, array $parameters = null, array $body = null)
    {
        $client = new Client;

        $url = 'https://'.$this->domain.'/admin/api/'.$this->version.'/'.$topic.'.json';

        if ($parameters !== null) {
            $url .= '?'.http_build_query($parameters, '', '&');
        }

        $options['headers'] = [
            'X-Shopify-Access-Token' => $this->token,
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
            throw new HitPayLogicException('Error while decoding response from Shopify: '.json_last_error_msg());
        }

        return $response;
    }
}
