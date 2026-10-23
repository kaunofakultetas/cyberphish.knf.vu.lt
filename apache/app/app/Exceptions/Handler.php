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
    // failed validation: Laravel's password fields and every
    // password field of the app's forms — the sign-up form's
    // repeat, the two change-password forms' new password and
    // its repeat, the manager form's pair — so no password
    // typed sits in the session store. None of these fields
    // shows its old value anyway.
    protected $dontFlash = [
        'password',
        'password_confirmation',
        'password2',
        'newpass',
        'renewpass',
        'pass',
        'repass',
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
