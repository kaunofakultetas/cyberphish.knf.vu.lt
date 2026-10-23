<?php

// -----------------------------------------------------------
//  [*] config/database.php — the MySQL connection
//
//  The app's one database: the MySQL 5.7 server of the
//  cyberphish-mysql container, whose schema mysql/init.sql
//  creates on the container's first start. The sqlite,
//  pgsql and sqlsrv connections and the Redis section are
//  Laravel's stock entries and go unused.
//
//  The file keeps the layout of Laravel 7's stock file,
//  which Laravel 13 still reads. Changed from it: the
//  database name, user and password lose their forge
//  defaults, and strict mode is off (the comment above
//  strict says what that does).
//
//  The cyberphish-apache service in docker-compose.yml sets
//  DB_HOST (cyberphish-mysql) and DB_DATABASE, DB_USERNAME
//  and DB_PASSWORD (the MYSQL_ variables the database
//  container is created with); the app ships no .env file.
//  It also sets REDIS_HOST to the cyberphish-redis
//  container, but nothing talks to Redis — no cache,
//  session or queue uses it, and the image has no phpredis
//  extension to reach it with.
//
//  Used by:
//    - Laravel's DatabaseManager — every model and every DB
//      query of the app
// -----------------------------------------------------------


use Illuminate\Support\Str;

return [

    // The connection models and queries use when they name
    // none, from DB_CONNECTION — not set, so mysql
    'default' => env('DB_CONNECTION', 'mysql'),

    // One entry per database Laravel supports out of the
    // box; only mysql is used
    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        // The cyberphish-mysql container on MySQL's port 3306;
        // database, user and password have no default, so the
        // environment must name them
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'unix_socket' => env('DB_SOCKET', ''),
            // The connection's character set; MySQL converts to
            // each column's own on write — latin1 in bei_managers
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            // Off: the session runs with sql_mode
            // NO_ENGINE_SUBSTITUTION alone, so MySQL cuts an
            // over-long value and stores a character the column
            // cannot hold as a question mark, with a warning
            // instead of an error
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

    ],

    // The table artisan migrate records its runs in; nothing
    // migrates, and mysql/init.sql creates no such table
    'migrations' => 'migrations',

    // Laravel's Redis connections, unused: nothing talks to
    // Redis
    'redis' => [

        // The client library, from REDIS_CLIENT — not set, so
        // phpredis, an extension the image does not have
        'client' => env('REDIS_CLIENT', 'phpredis'),

        // Cluster mode, and the prefix of every key: APP_NAME
        // as a slug plus _database_
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        // The connection broadcasting and the redis queue
        // would use; REDIS_HOST is cyberphish-redis
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        // The connection of the redis cache store, on its own
        // database
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
