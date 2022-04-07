<?php


namespace App\Services;


use App\Business;
use App\Business\Xero;
use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use XeroAPI\XeroPHP\Api\AccountingApi;
use XeroAPI\XeroPHP\Configuration;

/**
 * Class XeroApiFactory
 * @package App\Services
 */
class XeroApiFactory
{
    public static function makeAccountingApi(Business $business): AccountingApi
    {
        $accessToken = static::getAccessToken($business);

        return new AccountingApi(
            new Client(),
            Configuration::getDefaultConfiguration()->setAccessToken((string) $accessToken->getToken())
        );
    }

    public static function disconnect(Business $business)
    {
        $accessToken = static::getAccessToken($business);

        $connections = static::getConnections($accessToken);
        if(!empty($connections)) {
            $connectionID = $connections[0]['id'];

            $url = "https://api.xero.com/connections/{$connectionID}";
            $ch = curl_init($url);

            $headers = [
                'Authorization: Bearer ' . (string) $accessToken->getToken()
            ];

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_exec($ch);
            curl_close($ch);
        }
    }

    /**
     * @param Business $business
     * @return AccessToken|AccessTokenInterface
     * @throws IdentityProviderException
     */
    public static function getAccessToken(Business $business)
    {
        $xero = new Xero();
        $newAccessToken = $xero->provider->getAccessToken('refresh_token', [
            'refresh_token' => $business->xero_refresh_token
        ]);

        $business->xero_refresh_token = $newAccessToken->getRefreshToken();
        $business->update();

        return $newAccessToken;
    }

    public static function getConnections(AccessToken $accessToken): array
    {
        $url = "https://api.xero.com/connections";
        $ch = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . (string) $accessToken->getToken()
        ];

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }
}
