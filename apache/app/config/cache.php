<?php

// -----------------------------------------------------------
//  [*] config/cache.php — the cache stores
//
//  Laravel 7's stock cache file, which Laravel 13 reads
//  unchanged. CACHE_DRIVER is not set in
//  docker-compose.yml, so the default store is file, under
//  storage/framework/cache — a tmpfs in the
//  cyberphish-apache container, emptied by every restart.
//  Nothing uses the cache at the moment: the app makes no
//  Cache call, and the only throttles — the api group's and
//  VerificationController's — never run, because
//  routes/api.php holds no route and no route reaches
//  VerificationController. No other variable of this file
//  is set either.
//
//  Used by:
//    - Laravel's CacheManager, which nothing calls at the
//      moment
// -----------------------------------------------------------


use Illuminate\Support\Str;

return [

    // The store the Cache facade uses, from CACHE_DRIVER —
    // not set, so file; the in-process tests use array
    // (phpunit.xml). Laravel supports apc, array, database,
    // file, memcached, redis and dynamodb
    'default' => env('CACHE_DRIVER', 'file'),

    // One entry per store. database would need a cache table
    // mysql/init.sql does not create, and redis the phpredis
    // extension the image does not have
    'stores' => [

        'apc' => [
            'driver' => 'apc',
        ],

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

    ],

    // The prefix of every cache key, so stores shared with
    // other apps do not collide: CACHE_PREFIX, or APP_NAME as
    // a slug plus _cache — cyberphish_cache here
    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache'),

];
