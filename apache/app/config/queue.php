<?php

// -----------------------------------------------------------
//  [*] config/queue.php — the job queues
//
//  Laravel 7's stock queue file, unchanged. Nothing is
//  queued: the four mailables are sent with send() and none
//  implements ShouldQueue, and the app dispatches no job.
//  QUEUE_CONNECTION is not set in docker-compose.yml, so the
//  sync connection would run a job at once anyway; no queue
//  worker runs, and the failed_jobs table of the failed
//  section does not exist (mysql/init.sql). None of the
//  other variables here is set either.
//
//  Used by:
//    - Laravel's QueueManager, which nothing calls at the
//      moment
// -----------------------------------------------------------


return [

    // The connection jobs go to, from QUEUE_CONNECTION — not
    // set, so sync, which runs a job right away inside the
    // request that dispatched it
    'default' => env('QUEUE_CONNECTION', 'sync'),

    // One entry per back end; Laravel's drivers are sync,
    // database, beanstalkd, sqs, redis and null. database
    // would need a jobs table mysql/init.sql does not create
    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => 0,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'your-queue-name'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
        ],

    ],

    // Where a queue worker records the jobs that failed: the
    // failed_jobs table, which mysql/init.sql does not create
    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

];
