<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Exceptions\ZipException;

/**
 * Class Shopee
 * @package App\Helpers
 */
class ZipOauth
{
    public function __construct() {
      $res = $this->postRequest('https://' . config('services.zip.api_oauth_url'), [
        'client_id' => config('services.zip.client_id'),
        'client_secret' => config('services.zip.client_secret'),
        'audience' => config('services.zip.api_oauth_audience'),
        'grant_type' => 'client_credentials'
      ]);

      $this->token = $res->access_token;
    }

    /**
     * @param $endpoint
     * @param $data
     * @param $success
     * @return StdClass
     */
    public function postRequest ($endpoint, $data, $success = 200)
    {
      $client = new Client();

      $headers = isset($this->token)
        ? ['Authorization' => 'Bearer ' . $this->token]
        : [];

      $res = $client->post($endpoint, [ 
        'json' => $data,        
        'headers' => $headers
      ]);

      if ($res->getStatusCode() === $success) {
        return json_decode((string) $res->getBody());
      } else {
        $msg = 'Zip API failed with HTTP code: ' . $res->getStatusCode();
        Log::critical($msg);
        throw new ZipException($msg);
      }
    }

    /**
     * @param $endpoint
     * @param $data
     * @param $success
     * @return StdClass
     */
    public function getRequest ($endpoint, $data, $success = 200) {
      $client = new Client();

      $headers = isset($this->token)
        ? ['Authorization' => 'Bearer ' . $this->token]
        : [];

      $res = $client->get($endpoint, [ 
        'query' => $data,        
        'headers' => $headers
      ]);

      if ($res->getStatusCode() === $success) {
        return json_decode((string) $res->getBody());
      } else {
        $msg = 'Zip API failed with HTTP code: ' . $res->getStatusCode();
        Log::critical($msg);
        throw new ZipException($msg);
      }
    }
}
