<?php

// -----------------------------------------------------------
//  [*] config/view.php — where Blade reads and compiles
//
//  Laravel 7's stock view file, unchanged: the templates
//  come from resources/views, and their compiled PHP goes
//  to storage/framework/views — a tmpfs in the
//  cyberphish-apache container, so a restart empties it and
//  each template compiles again on its first use.
//  VIEW_COMPILED_PATH is not set in docker-compose.yml.
//
//  Used by:
//    - Laravel's ViewServiceProvider — every view the
//      controllers, the mails and the certificate PDF render
// -----------------------------------------------------------


return [

    // The folders Blade looks for templates in
    'paths' => [
        resource_path('views'),
    ],

    // The folder of the compiled templates, from
    // VIEW_COMPILED_PATH — not set, so storage/framework/views.
    // The folder must exist when the config loads: realpath
    // gives false otherwise, and Blade refuses to start
    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

];
