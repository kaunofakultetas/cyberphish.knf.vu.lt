<?php

// -----------------------------------------------------------
//  [*] config/broadcasting.php — where broadcast events go
//
//  Laravel 7's stock broadcasting file, which Laravel 13
//  reads unchanged. The app broadcasts nothing: no event
//  implements ShouldBroadcast, and
//  App\Providers\BroadcastServiceProvider is switched off
//  in config/app.php. BROADCAST_DRIVER is not set in
//  docker-compose.yml, so the null broadcaster would drop
//  anything sent anyway, and none of the PUSHER_ variables
//  is set either.
//
//  Used by:
//    - Laravel's BroadcastManager, which nothing calls at the
//      moment
// -----------------------------------------------------------


return [

    // The broadcaster events go to, from BROADCAST_DRIVER —
    // not set, so null, which discards them. Laravel
    // supports pusher, redis, log and null
    'default' => env('BROADCAST_DRIVER', 'null'),

    // One entry per broadcaster: Pusher with its keys from
    // the PUSHER_ variables, Redis over the default
    // connection of config/database.php, the log, and null
    'connections' => [

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => true,
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
