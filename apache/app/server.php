<?php

// -----------------------------------------------------------
//  [*] server.php — the router of artisan serve
//
//  The router script PHP's built-in web server runs under
//  artisan serve, standing in for Apache's mod_rewrite: a
//  path that names a file under public/ is left to the
//  built-in server, every other request goes to
//  public/index.php. Production never runs it — Apache
//  serves public/ and this file lies outside it (the
//  Apache suite's ProtectionTest checks that /server.php is
//  a 404) — and the image's php.ini disables the functions
//  that start processes, so artisan serve could not start
//  there anyway. Laravel 7's stock file, unchanged.
//
//  Used by:
//    - artisan serve, which nothing runs at the moment
// -----------------------------------------------------------


// The path asked for, without its query string
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// A real file under public/: false tells the built-in
// server to send it as it is
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

// Anything else goes to the front controller
require_once __DIR__.'/public/index.php';
