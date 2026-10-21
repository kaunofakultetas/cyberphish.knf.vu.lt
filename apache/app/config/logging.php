<?php

// -----------------------------------------------------------
//  [*] config/logging.php — where the log goes
//
//  Laravel 7's stock logging file, unchanged. LOG_CHANNEL
//  is not set in docker-compose.yml, so everything goes
//  through the stack channel into the single file
//  storage/logs/laravel.log, at debug level, never rotated
//  by Laravel; docker-compose.yml binds that folder to
//  _LOGS/apache/laravel_logs on the host. The app writes no
//  log line of its own — what lands there are the errors
//  the exception handler reports. None of the other
//  variables here is set either.
//
//  Used by:
//    - Laravel's LogManager — the exception handler
//      (app/Exceptions/Handler.php) reports every uncaught
//      error through it
// -----------------------------------------------------------


use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    // The channel the log goes to, from LOG_CHANNEL — not
    // set, so stack, which hands everything to single
    'default' => env('LOG_CHANNEL', 'stack'),

    // One entry per channel; Laravel's drivers are single,
    // daily, slack, syslog, errorlog, monolog, custom and
    // stack. emergency is the file Laravel falls back to
    // when a channel cannot be built
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => 'debug',
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
    ],

];
