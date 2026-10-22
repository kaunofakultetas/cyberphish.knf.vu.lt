<?php

// -----------------------------------------------------------
//  [*] bootstrap/app.php — builds the application
//
//  Creates the Laravel application — the service container
//  every part of the app is resolved from — rooted at the
//  app's folder, binds the app's own HTTP kernel, console
//  kernel and exception handler to the framework's
//  interfaces, and returns it to the script that required
//  it. It only builds: handling a request or running a
//  command is the caller's job. Laravel 7's stock file,
//  unchanged; Laravel 13 still boots from it.
//
//  Used by:
//    - public/index.php — every web request
//    - artisan — every console command, the image build's
//      package:discover among them
//    - CreatesApplication (tests/) — every test of the
//      suite
// -----------------------------------------------------------


// The application, rooted at the folder above this one;
// APP_BASE_PATH could move it, and nothing sets it
$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

// The app's own classes stand in for the framework's
// interfaces: the HTTP kernel serves web requests, the
// console kernel runs artisan, and the handler reports and
// renders every uncaught error
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

// Handed back to the caller, which runs it
return $app;
