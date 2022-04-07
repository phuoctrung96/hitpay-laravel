<?php


namespace App\Services\Quickbooks;


use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2AccessToken;
use QuickBooksOnline\API\DataService\DataService;

/**
 * Class AuthorizationService
 * @package App\Services\Quickbooks
 */
class AuthorizationService
{
    /**
     * @var \QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper
     */
    private $oAuth2LoginHelper;

    public function __construct(string $clientId, string $clientSecret, string $redirectUri, string $scope, string $baseUrl)
    {
        $dataService = DataService::Configure(array(
            'auth_mode' => 'oauth2',
            'ClientID' => $clientId,
            'ClientSecret' => $clientSecret,
            'RedirectURI' => $redirectUri,
            'scope' => $scope,
            'baseUrl' => $baseUrl
        ));

        $this->oAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
    }

    public function getAuthorizationUrl(): string
    {
        return $this->oAuth2LoginHelper->getAuthorizationCodeURL();
    }

    public function exchangeAuthorizationCodeForToken(string $code, string $realmId)
    {
        return $this->oAuth2LoginHelper->exchangeAuthorizationCodeForToken($code, $realmId);
    }

    public function refreshToken(string $token): OAuth2AccessToken
    {
        return $this->oAuth2LoginHelper->refreshAccessTokenWithRefreshToken($token);
    }

    public function revokeToken(string $token): bool
    {
        return $this->oAuth2LoginHelper->revokeToken($token);
    }
}
