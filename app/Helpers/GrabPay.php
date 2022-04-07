<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Exceptions\GrabPayException;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Str;

/**
 * Class GrabPay
 * @package App\Helpers
 */
class GrabPay
{
    public static function urlsafe_base64encode($value) {
      return str_replace(['=', '+', '/'], ['', '-', '_'], base64_encode($value));
    }

    /**
     * @param $endpoint
     * @param $data
     * @param $success
     * @return Array
     */
    public static function postRequest($endpoint, $data, $headers = [], $date = false, $success = 200) {
      $client = new Client([
        'base_uri' => 'https://' . config('services.grabpay.domain')
      ]);

      try {
        $res = $client->post($endpoint, [ 
          'body' => json_encode($data),
          'headers' => array_merge([
            'Content-Type' => 'application/json'
          ], $headers)
        ]);
  
        if ($res->getStatusCode() === $success) {
          if (isset($res->errcode) && $res->errcode > 0) {
            self::error($endpoint, $data, "Error code: " . $res->errcode . ", debug msg: " . $res->debug_msg);
          } else {
            return json_decode((string) $res->getBody());
          }
        } else {
          self::error($endpoint, $data, "HTTP status: " . $res->getStatusCode() . ", content: " . (string) $res->getBody());
        }  
      } catch (ClientException $exception) {
        self::error($endpoint, $data, $exception->getResponse()->getBody()->getContents());
      }
    }

    static function error ($endpoint, $data, $response) {
      throw new GrabPayException("[GrabPay] API failed with error\nEndpoint: " . $endpoint . "\n" .
        "Data: " . json_encode($data) . "\nResponse:" . $response);
    }

    public static function chargeInitRequest($endpoint, $data, $provider, $success = 200) {
      $requestData = json_encode($data);

      $hash = base64_encode(hash(
        'sha256',
        $requestData,
        true // binary
      ));

      $date = gmdate("D, d M Y H:i:s") . ' GMT';

      $signData = [
        'POST',
        'application/json',
        $date,
        $endpoint,
        $hash
      ];

      $signLine = implode("\n", $signData) . "\n";

      $sign = base64_encode(hash_hmac(
        'sha256',
        $signLine,
        config('services.grabpay.partner_secret'),
        true // binary
      ));

      return self::postRequest($endpoint, $data, [
        'Authorization' => config('services.grabpay.partner_id') . ':' . $sign,
        'Date' => $date
      ]);
    }
}
