<?php

use Monolog\Handler\NullHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => [
                'daily',
                'slack',
            ],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/hitpay.log'),
            'level' => 'debug',
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/hitpay.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        'xero' => [
            'driver' => 'daily',
            'path' => storage_path('logs/xero.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        'quickbooks-invoices' => [
            'driver' => 'daily',
            'path' => storage_path('logs/quickbooks/invoices.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        'available-balance-payouts' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_AVAILABLE_BALANCE_PAYOUT_WEBHOOK_URL'),
            'username' => 'HitPay Collection'.(env('APP_ENV') === 'production' ? '' : ' ('.ucfirst(env('APP_ENV')).')'),
            'short' => true,
            'emoji' => 'bug',
            'level' => 'info',
        ],

        'quickbooks-errors' => [
            'driver' => 'daily',
            'path' => storage_path('logs/quickbooks/errors.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        'pusher_hotfix' => [
            'driver' => 'daily',
            'path' => storage_path('logs/pusher_hotfix.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'channel' => env('LOG_SLACK_WEBHOOK_CHANNEL'),
            'username' => 'HitPay Log',
            'short' => true,
            'context' => false,
            'emoji' => 'bug',
            'level' => 'warning',
        ],

        'failed-collection' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_FAILED_COLLECTION_WEBHOOK_URL'),
            'username' => 'HitPay Collection'.(env('APP_ENV') === 'production' ? '' : ' ('.ucfirst(env('APP_ENV')).')'),
            'short' => true,
            'emoji' => 'bug',
            'level' => 'info',
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],
    ],

];
