<?php


namespace App\Business;


use League\OAuth2\Client\Provider\GenericProvider;

class Xero
{
    public $provider;

    public const INVOICE_TYPE='ACCREC'; // Invoice type for sales
    public const INVOICE_STATUS = 'AUTHORISED';
    public const INVOICE_STATUS_PAID = 'PAID';
    public const PAYMENT_STATUS = 'AUTHORISED';
    public const INVOICE_GROUPING_INDIVIDUAL = 'INDIVIDUAL';
    public const INVOICE_GROUPING_BULK = 'BULK';

    public const XERO_ACCOUT_TYPES = [
        'ASSET', 'EQUITY', 'EXPENSE', 'LIABILITY', 'REVENUE'
    ];

    public const INVOICE_GROUPING_VARIANTS = [
        self::INVOICE_GROUPING_BULK => 'Lumpsum for each day',
        self::INVOICE_GROUPING_INDIVIDUAL => 'By individual transaction',
    ];

    public const SALES_ACCOUNT_CODE = 200;
    public $appScope = [
        'scope' => ['openid email profile offline_access accounting.settings accounting.transactions accounting.contacts paymentservices'] // need only those scopes
    ];
    public function __construct($forLogin = false)
    {
        if($forLogin) {
            $this->provider = new GenericProvider(
                [
                    'clientId'                => config('app.xero.client_id'),
                    'clientSecret'            => config('app.xero.secret_key'),
                    'redirectUri'             => 'https://'.config('app.subdomains.dashboard')."/auth/xero/callback",
                    'urlAuthorize'            => config('app.xero.login_url'),
                    'urlAccessToken'          => config('app.xero.access_token_url'),
                    'urlResourceOwnerDetails'          => config('app.xero.organization_url'),
                ]
            );
        } else {
            $this->provider = new GenericProvider(
                [
                    'clientId'                => config('app.xero.client_id'),
                    'clientSecret'            => config('app.xero.secret_key'),
                    'redirectUri'             => 'https://'.config('app.subdomains.dashboard')."/integration/xero/callback",
                    'urlAuthorize'            => config('app.xero.login_url'),
                    'urlAccessToken'          => config('app.xero.access_token_url'),
                    'urlResourceOwnerDetails'          => config('app.xero.organization_url'),
                ]
            );
        }

    }

    public function authorize()
    {
        $authorizeURL = $this->provider->getAuthorizationUrl($this->appScope);
        if (!strlen($authorizeURL))
        {
            return false;
        }
        $state =  $this->provider->getState();
        session()->put('xero_authorize_status', $state);
        return $authorizeURL;
    }

    public function authorizeLogin()
    {
        $authorizeURL = $this->provider->getAuthorizationUrl($this->appScope);
        if (!strlen($authorizeURL))
        {
            return false;
        }
        $state =  $this->provider->getState();
        session()->put('xero_authorize_status', $state);
        return $authorizeURL;
    }
}
