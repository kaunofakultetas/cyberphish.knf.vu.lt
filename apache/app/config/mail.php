<?php

// -----------------------------------------------------------
//  [*] config/mail.php — how the site sends mail
//
//  Every mail of the site — the registration verification,
//  the password reset link and the new password, the
//  simulation results — goes out through the smtp mailer,
//  synchronously, to Mailjet's relay. The cyberphish-apache
//  service in docker-compose.yml sets MAIL_MAILER (smtp),
//  MAIL_HOST (in-v3.mailjet.com), MAIL_PORT (587) and
//  MAIL_ENCRYPTION (tls), the Mailjet API key and secret as
//  MAIL_USERNAME and MAIL_PASSWORD, and the sender as
//  MAIL_FROM_ADDRESS and MAIL_FROM_NAME; the app ships no
//  .env file. The container reaches in-v3.mailjet.com only
//  through the exit proxy (extra_hosts).
//
//  Changed from Laravel 7's stock file: the from address
//  has no default and the name defaults to CyberPhish, and
//  a mailjet entry is added — settings in the shape of a
//  Mailjet API package the app does not install. The entry
//  has no transport, so choosing it would fail, and nothing
//  chooses it; its MAILJET_APIKEY and MAILJET_APISECRET are
//  not passed to the container, which gets the key and
//  secret as the SMTP user and password instead.
//
//  Used by:
//    - Laravel's MailManager — the sends of RegisterController
//      (SendVerification), LoginController (SendRemindLink,
//      SendNewPass) and SimulationsController
//      (SimulationsResults)
// -----------------------------------------------------------


return [

    // The mailer mail goes through, from MAIL_MAILER — smtp
    // in production, array in the in-process tests
    // (phpunit.xml)
    'default' => env('MAIL_MAILER', 'smtp'),

    // One entry per mailer; Laravel's transports are smtp,
    // sendmail, mailgun, ses, postmark, log and array. smtp
    // reads the MAIL_ variables docker-compose.yml sets
    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'auth_mode' => null,
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'mailgun' => [
            'transport' => 'mailgun',
        ],

        'postmark' => [
            'transport' => 'postmark',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => '/usr/sbin/sendmail -bs',
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        // Left over from a Mailjet API package the app does not
        // install: with no transport key the MailManager could
        // not build it, and MAIL_MAILER never names it
        'mailjet' => [
            'key' => env('MAILJET_APIKEY'),
            'secret' => env('MAILJET_APISECRET'),
            'transactional' => [
                'call' => true,
                'options' => [
                    'url' => 'api.mailjet.com',
                    'version' => 'v3.1',
                    'call' => true,
                    'secured' => true
                ]
            ],

            'common' => [
                'call' => true,
                'options' => [
                    'url' => 'api.mailjet.com',
                    'version' => 'v3',
                    'call' => true,
                    'secured' => true
                ]

            ]

        ]
    ],

    // The sender of every mail: MAIL_FROM_ADDRESS, with no
    // default, and MAIL_FROM_NAME — noreply@cyberphish.knf.vu.lt
    // and CYBERPHISH in production
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS'),
        'name' => env('MAIL_FROM_NAME', 'CyberPhish'),
    ],

    // The theme and component paths of Markdown mails; the
    // site's mails are plain Blade views from the emails
    // folder of resources/views, so this goes unused
    'markdown' => [
        'theme' => 'default',

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

];
