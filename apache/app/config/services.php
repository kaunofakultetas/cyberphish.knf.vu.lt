<?php

// -----------------------------------------------------------
//  [*] config/services.php — third-party service keys
//
//  Laravel 7's stock services file, which Laravel 13 reads
//  unchanged: the credentials of the mailgun, postmark and
//  ses mail transports, the place packages look for such
//  keys. None is used — mail goes through the smtp mailer
//  (config/mail.php) — and none of the variables is set in
//  docker-compose.yml.
//
//  Used by:
//    - Laravel's MailManager, for the mailgun, postmark and
//      ses transports, which nothing selects at the moment
// -----------------------------------------------------------


return [

    // Mailgun's domain, API secret and endpoint
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    // Postmark's server token
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    // The AWS keys and region of Amazon SES
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
