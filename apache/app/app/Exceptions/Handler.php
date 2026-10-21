<?php

// -----------------------------------------------------------
//  [*] Handler — what becomes of an uncaught exception
//
//  Laravel's exception handler with nothing of the app's
//  own. An exception is logged (storage/logs, through the
//  stack channel) unless it is one of Laravel's routine
//  ones — an HTTP error such as a 404, a failed
//  validation, a refused sign-in, a CSRF mismatch. A crash
//  or an HTTP error shows Laravel's generic error page
//  (the app has no error views), with no detail while
//  APP_DEBUG is off, as in production.
//
//  Used by:
//    - bootstrap/app.php — bound as the exception handler
// -----------------------------------------------------------


namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;








// -----------------------------------------------------------
// Handler
// -----------------------------------------------------------
//
// Reports and renders through Laravel's handler unchanged;
// the two lists below are all it sets.
//
// Known bug (low): dontFlash holds Laravel's password
// fields only (Handler.php:64), so a failed validation
// flashes the registration form's password2, or the
// manager form's pass and repass, into the session with
// the rest of the old input — plain-text passwords in the
// session store until the next request takes them out.
// Fix: add those names to the list.
// Pinned as password-flash.
//
// Used by:
//   - bootstrap/app.php — bound as the exception handler
//   - Laravel's HTTP and console kernels — every uncaught
//     exception
// -----------------------------------------------------------

class Handler extends ExceptionHandler
{

    // The exception classes never logged, on top of
    // Laravel's routine ones — none
    protected $dontReport = [
    ];

    // The inputs never flashed back to the form after a
    // failed validation
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];






    // -----------------------------------------------------------
    // report
    // -----------------------------------------------------------
    //
    // Logs the exception through Laravel's handler, unchanged.
    //
    // Used by:
    //   - Laravel's HTTP and console kernels — every uncaught
    //     exception
    // -----------------------------------------------------------

    public function report(Throwable $exception)
    {
        parent::report($exception);
    }






    // -----------------------------------------------------------
    // render
    // -----------------------------------------------------------
    //
    // Turns the exception into a response through Laravel's
    // handler, unchanged: the redirect back with errors of a
    // failed validation, the login redirect of a refused
    // sign-in check, the page of an HTTP error — 404, 403,
    // the 419 of a failed CSRF check — and the generic 500
    // page for everything else.
    //
    // Used by:
    //   - Laravel's HTTP kernel — every exception a request
    //     ends in
    // -----------------------------------------------------------

    public function render($request, Throwable $exception)
    {
        return parent::render($request, $exception);
    }
}
