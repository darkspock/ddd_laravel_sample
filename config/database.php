<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'read' => [
                'host' => [
                    env('DB_HOST_READ', '127.0.0.1'),
                ],
            ],
            'write' => [
                'host' => [
                    env('DB_HOST_WRITE', '127.0.0.1')
                ],
            ],
            'sticky' => true,
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
            'sslrootcert' => env('DB_SSLROOTCERT', '/var/www/.postgresql/root.crt'),
            'options'=>[
                'sslmode' => env('DB_SSLMODE', 'prefer'),
                'sslrootcert' => env('DB_SSLROOTCERT', '/var/www/.postgresql/root.crt'),
            ]
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations_crm',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [
        'client' => env('REDIS_CLIENT', 'predis'),
        'options' => [
            'cluster' => 'redis', // Enable Redis clustering
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],
        'clusters' => [
            'default' => [
                [
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'username' => env('REDIS_USERNAME'),
                    'password' => env('REDIS_PASSWORD'),
                    'port' => env('REDIS_PORT', '6379'),
                    'scheme' => env('REDIS_SCHEME', 'tcp'),
                    'database' => 0, // Must be 0 in cluster mode
                    'ssl' => [
                        'verify_peer' => false, // Disable for AWS self-signed certs
                        'verify_peer_name' => false,
                    ],
                ],
            ],
        ],
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'database' => 0, // Must be 0 in cluster mode
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ],
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'database' => 0, // Must be 0 in cluster mode
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ],
    ],

    'opensearch' => [
        'host' => env('OPENSEARCH_HOST', 'http://localhost:9200'),
        'user' => env('OPENSEARCH_USER', 'admin'),
        'password' => env('OPENSEARCH_PASSWORD', 'admin'),
    ]
];
