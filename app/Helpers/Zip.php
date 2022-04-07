<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Exceptions\ZipException;

/**
 * Class Shopee
 * @package App\Helpers
 */
class Zip
{
    /**
     * @param $endpoint
     * @param $data
     * @param $success
     * @return Array
     */
    public static function postRequest ($endpoint, $data, $successCode = 200)
    {
      $client = new Client([
        'base_uri' => 'https://' . config('services.zip.api_url')
      ]);

      $idempotency = (string) Str::uuid();

      $success = false;
      $retry = 1;

      do {
        try {
          $res = $client->post($endpoint, [ 
            'json' => $data,
            'headers' => [
              'Authorization' => 'Bearer ' . config('services.zip.api_key'),
              'Zip-Version' => '2021-08-25',
              'Idempotency-Key' => $idempotency
            ],
            // A 15 second allowance for initial API response from Zip
            'connect_timeout' => 15
          ]);

          $success = true;
  
        } catch (ConnectException $e) {
          $retry++;

          if ($retry < 5) {
            // A 5 second spacing between subsequent retry attempts
            sleep(5);
          } else {
            throw $e;
          }
        } 
  
      } while (!$success);

      if ($res->getStatusCode() === $successCode) {
        return json_decode((string) $res->getBody());
      } else {
        $msg = 'Zip API failed with HTTP code: ' . $res->getStatusCode();
        Log::critical($msg);
        throw new ZipException($msg);
      }
    }
}
