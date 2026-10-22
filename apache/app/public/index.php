<?php

// -----------------------------------------------------------
//  [*] public/index.php — the front controller
//
//  Every web request that names no file under public/ ends
//  here: Apache's DocumentRoot is public/, and
//  public/.htaccess rewrites every other path to this
//  script. It loads Composer's autoloader, builds the app
//  (bootstrap/app.php), hands the request to the HTTP
//  kernel — the global middleware, the routes, the
//  controllers — sends the response back, then lets the
//  kernel finish its after-response work. Laravel 7's stock
//  file, unchanged; Laravel 13 still runs it.
//
//  Used by:
//    - Apache — every request public/.htaccess rewrites,
//      and /index.php asked for by name
//    - FrontControllerTest (tests/Apache) — the home page
//      both ways, over HTTP
// -----------------------------------------------------------


// The moment the request started; nothing in the app reads
// it
define('LARAVEL_START', microtime(true));

// Composer's class loader, for the app's classes and the
// vendor packages
require __DIR__.'/../vendor/autoload.php';

// The application, as bootstrap/app.php builds it
$app = require_once __DIR__.'/../bootstrap/app.php';

// The request, captured from PHP's globals, goes through
// the HTTP kernel; the response goes back to the browser,
// and terminate runs what waits for the response to be
// sent
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
