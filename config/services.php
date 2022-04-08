<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'ses' => [
        'key' => env('AWS_SES_ACCESS_KEY_ID'),
        'secret' => env('AWS_SES_SECRET_ACCESS_KEY'),
        'region' => env('AWS_SES_DEFAULT_REGION', 'us-east-1'),
    ],

    'stripe' => [
        'my' => [
            'key' => env('STRIPE_MY_KEY'),
            'secret' => env('STRIPE_MY_SECRET'),
            'client_id' => env('STRIPE_MY_CLIENT_ID'),
            'endpoint_secret' => env('STRIPE_MY_ENDPOINT_SECRET'),
            'endpoint_secret_connect' => env('STRIPE_MY_ENDPOINT_SECRET_CONNECT'),
        ],

        'sg' => [
            'key' => env('STRIPE_SG_KEY'),
            'secret' => env('STRIPE_SG_SECRET'),
            'client_id' => env('STRIPE_SG_CLIENT_ID'),
            'endpoint_secret' => env('STRIPE_SG_ENDPOINT_SECRET'),
            'endpoint_secret_connect' => env('STRIPE_SG_ENDPOINT_SECRET_CONNECT'),
            'stripe_custom_account_production' => env('STRIPE_CUSTOM_ACCOUNT_PRODUCTION', false),
            'stripe_custom_account_positive_test_mode' => env('STRIPE_CUSTOM_ACCOUNT_POSITIVE_TEST_MODE', false),
        ],
    ],

    'slack' => [
        'new_charges' => env('SLACK_WEBHOOK_URL_NEW_CHARGES'),
        'new_businesses' => env('SLACK_WEBHOOK_URL_NEW_BUSINESSES'),
        'pending_payouts' => env('SLACK_WEBHOOK_URL_PENDING_PAYOUTS'),
        'failed_callbacks' => env('SLACK_WEBHOOK_URL_FAILED_CALLBACKS'),
        'failed_refunds' => env('SLACK_WEBHOOK_URL_FAILED_REFUNDS'),
        'non_identifiable_charge' => env('SLACK_WEBHOOK_URL_NON_IDENTIFIABLE_CHARGE'),
    ],

    'firebase' => [
        'secret' => env('FIREBASE_SECRET'),
    ],

    'shopify' => [
        'client_id' => env('SHOPIFY_KEY'),
        'client_secret' => env('SHOPIFY_SECRET'),
        'redirect' => null,
        'client_id_v2' => env('SHOPIFY_KEY_V2'),
        'client_secret_v2' => env('SHOPIFY_SECRET_V2'),
        'redirect_v2' => null,
    ],

    'dbs' => [
        'cutoff' => env('DBS_CUTOFF', true),
        'organization_id' => env('DBS_ORGANIZATION_ID'),
        'corporate_name' => env('DBS_CORPORATE_NAME'),
        'corporate_account_number' => env('DBS_CORPORATE_ACCOUNT_NUMBER'),
        'api_key' => env('DBS_API_KEY'),
        'file_password' => env('DBS_FILE_PASSWORD'),
        'paynow' => [
            'status' => env('DBS_PAYNOW_STATUS', true),
            'status_from' => env('DBS_PAYNOW_STATUS_FROM'),
            'status_to' => env('DBS_PAYNOW_STATUS_TO'),
        ],
    ],

    'xero' => [
        'client_id' => env('XERO_CLIENT_ID'),
        'client_secret' => env('XERO_SECRET_KEY'),
        'redirect' => _env_domain('dashboard', true) . '/auth/xero/callback',
    ],

    'singpass' => [
        'client_id' => env('MY_INFO_CLIENT_ID', 'STG2-MYINFO-SELF-TEST'), // test client id
        'client_secret' => env('MY_INFO_APP_CLIENT_SECRET', '44d953c796cccebcec9bdc826852857ab412fbe2'), // test client secret
        'auth_level' => env('MY_INFO_APP_AUTH_LEVEL', 'L0'), // test auth level, should be L2
        'client_id_individual' => env('MY_INFO_CLIENT_ID_INDIVIDUAL', 'STG2-MYINFO-SELF-TEST'),
        'client_secret_individual' => env('MY_INFO_APP_CLIENT_SECRET_INDIVIDUAL', '44d953c796cccebcec9bdc826852857ab412fbe2'),
    ],

    'shopee' => [
        'client_id' => env('SHOPEE_CLIENT_ID'),
        'secret_key' => env('SHOPEE_SECRET_KEY'),
        'domain' => env('SHOPEE_DOMAIN'),
        'enabled' => env('SHOPEE_ENABLE'),
        'whitelist' => env('SHOPEE_WHITELIST')
    ],

    'hoolah' => [
      'username' => env('HOOLAH_USERNAME'),
      'password' => env('HOOLAH_PASSWORD'),
      'domain' => env('HOOLAH_DOMAIN'),
      'onboarding_domain' => env('HOOLAH_ONBOARDING_DOMAIN'),
      'redirect_domain' => env('HOOLAH_REDIRECT_DOMAIN'),
    ],

    'quickbooks' => [
        'client_id' => env('QUICKBOOKS_CLIENT_ID', 'ABoAjVvtc1CTAhOJRsZiu7vbmZJ6T79LXRUSw22YSDojno7SM6'),
        'client_secret' => env('QUICKBOOKS_SECRET_KEY', '1OqMbxL1cEhfJsK2bXmAMRsh9D8MoRg4Fwawn895'),
        'redirect' => _env_domain('dashboard', true) . '/integration/quickbooks/callback',
        'authorizationRequestUrl' => 'https://appcenter.intuit.com/connect/oauth2',
        'tokenEndPointUrl' => 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer',
        'oauth_scope' => 'com.intuit.quickbooks.accounting openid profile email phone address',
        'baseUrl' => env('QUICKBOOKS_BASE_URL', 'https://sandbox-quickbooks.api.intuit.com/'),
    ],

    'grabpay' => [
      'onboarding_email' => env('GRABPAY_ONBOARDING_EMAIL'),
      'domain' => env('GRABPAY_DOMAIN'),
      'redirect_uri' => env('GRABPAY_REDIRECT_URI'),
      'partner_id' => env('GRABPAY_PARTNER_ID'),
      'partner_secret' => env('GRABPAY_PARTNER_SECRET'),
      'client_id' => env('GRABPAY_CLIENT_ID'),
      'client_secret' => env('GRABPAY_CLIENT_SECRET'),
      'enabled' => env('GRABPAY_DIRECT_ENABLE'),
      'whitelist' => env('GRABPAY_DIRECT_WHITELIST')
    ],

    'zip' => [
      'api_url' => env('ZIP_API_URL'),
      'api_key' => env('ZIP_API_KEY'),
      'api_merchant_url' => env('ZIP_MERCHANT_API_URL'),
      'api_oauth_url' => env('ZIP_OAUTH_URL'),
      'api_oauth_audience' => env('ZIP_OAUTH_AUDIENCE'),
      'client_id' => env('ZIP_CLIENT_ID'),
      'client_secret' => env('ZIP_CLIENT_SECRET'),
      'enabled' => env('ZIP_ENABLE'),
      'whitelist' => env('ZIP_WHITELIST')
    ],

    'posthog' => [
        'api_key' => env('POST_HOG_API_KEY', 'phc_Dgzek30pf9fyiCI9DOFCgCJWKpj5yUDNSg7YGVYxCnr'),
        'api_host' => env('POST_HOG_API_HOST', 'https://posthog.hitpayapp.com'),
    ],

    'comply_advantage' => [
        'key' => env('COMPLY_ADVANTAGE_KEY'),
    ],

    'cognito' => [
        'publishable_key' => env('COGNITO_PUBLISHABLE_KEY'),
        'key' => env('COGNITO_KEY'),
        'secret' => env('COGNITO_SECRET'),
        'template_id' => env('COGNITO_TEMPLATE_ID'),
        'api_url' => env('COGNITO_API_URL'),
        'production_ready' => env('COGNITO_PRODUCTION_READY'),
    ],

    'freecurrencyapi' => [
        'api_key' => env('FREECURRENCYAPI_API_KEY')
    ]
];
