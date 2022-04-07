<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => 'mysql',

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'read' => [
                'host' => explode('|', env('DB_HOST_READ', env('DB_HOST', '127.0.0.1'))),
            ],
            'write' => [
                'host' => explode('|', env('DB_HOST_WRITE', env('DB_HOST', '127.0.0.1'))),
            ],
            'sticky' => true,
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'hitpay'),
            'username' => env('DB_USERNAME', 'hitpay'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mysql_old' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'read' => [
                'host' => explode('|', env('DB_OLD_HOST_READ', env('DB_OLD_HOST', '127.0.0.1'))),
            ],
            'write' => [
                'host' => explode('|', env('DB_OLD_HOST_WRITE', env('DB_OLD_HOST', '127.0.0.1'))),
            ],
            'sticky' => true,
            'port' => env('DB_OLD_PORT', '3306'),
            'database' => env('DB_OLD_DATABASE', 'hitpay'),
            'username' => env('DB_OLD_USERNAME', 'hitpay'),
            'password' => env('DB_OLD_PASSWORD', ''),
            'unix_socket' => env('DB_OLD_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => 'phpredis',

        'options' => [
            'cluster' => 'redis',
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DB', 0),
        ],

        'session' => [
            'url' => env('REDIS_SESSION_URL', env('REDIS_URL')),
            'host' => env('REDIS_SESSION_HOST', env('REDIS_HOST', '127.0.0.1')),
            'password' => env('REDIS_SESSION_PASSWORD', env('REDIS_PASSWORD', null)),
            'port' => env('REDIS_SESSION_PORT', env('REDIS_PORT', 6379)),
            'database' => env('REDIS_SESSION_DB', 1),
        ],

        'cache' => [
            'url' => env('REDIS_CACHE_URL', env('REDIS_URL')),
            'host' => env('REDIS_CACHE_HOST', env('REDIS_HOST', '127.0.0.1')),
            'password' => env('REDIS_CACHE_PASSWORD', env('REDIS_PASSWORD', null)),
            'port' => env('REDIS_CACHE_PORT', env('REDIS_PORT', 6379)),
            'database' => env('REDIS_CACHE_DB', 2),
        ],

        'queue' => [
            'url' => env('REDIS_QUEUE_URL', env('REDIS_URL')),
            'host' => env('REDIS_QUEUE_HOST', env('REDIS_HOST', '127.0.0.1')),
            'password' => env('REDIS_QUEUE_PASSWORD', env('REDIS_PASSWORD', null)),
            'port' => env('REDIS_QUEUE_PORT', env('REDIS_PORT', 6379)),
            'database' => env('REDIS_QUEUE_DB', 3),
        ],

        'horizon' => [
            'url' => env('REDIS_HORIZON_URL', env('REDIS_URL')),
            'host' => env('REDIS_HORIZON_HOST', env('REDIS_HOST', '127.0.0.1')),
            'password' => env('REDIS_HORIZON_PASSWORD', env('REDIS_PASSWORD', null)),
            'port' => env('REDIS_HORIZON_PORT', env('REDIS_PORT', 6379)),
            'database' => env('REDIS_HORIZON_DB', 4),
        ],

        'broadcast' => [
            'url' => env('REDIS_BROADCAST_URL', env('REDIS_URL')),
            'host' => env('REDIS_BROADCAST_HOST', env('REDIS_HOST', '127.0.0.1')),
            'password' => env('REDIS_BROADCAST_PASSWORD', env('REDIS_PASSWORD', null)),
            'port' => env('REDIS_BROADCAST_PORT', env('REDIS_PORT', 6379)),
            'database' => env('REDIS_BROADCAST_DB', 5),
        ],

        'insight' => [
            'url' => env('REDIS_INSIGHT_URL', env('REDIS_URL')),
            'host' => env('REDIS_INSIGHT_HOST', env('REDIS_HOST', '127.0.0.1')),
            'password' => env('REDIS_INSIGHT_PASSWORD', env('REDIS_PASSWORD', null)),
            'port' => env('REDIS_INSIGHT_PORT', env('REDIS_PORT', 6379)),
            'database' => env('REDIS_INSIGHT_DB', 6),
        ],

    ],

];
