<?php

// -----------------------------------------------------------
//  [*] AppServiceProvider — the app's own provider
//
//  Laravel's place for the app's own container bindings
//  and boot-time wiring. The app binds nothing; at boot it
//  keeps the paginator on the Bootstrap 4 template the
//  pages were built with, and makes the reCAPTCHA rule
//  survive an outage of Google.
//
//  Used by:
//    - config/app.php — the providers list
// -----------------------------------------------------------


namespace App\Providers;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;








// -----------------------------------------------------------
// AppServiceProvider
// -----------------------------------------------------------
//
// Registers nothing; boots the paginator's template and
// the reCAPTCHA rule.
//
// Used by:
//   - config/app.php — the providers list
// -----------------------------------------------------------

class AppServiceProvider extends ServiceProvider
{






    // -----------------------------------------------------------
    // register
    // -----------------------------------------------------------
    //
    // Binds nothing into the container.
    //
    // Used by:
    //   - Laravel, while it registers the providers of
    //     config/app.php
    // -----------------------------------------------------------

    public function register()
    {
    }






    // -----------------------------------------------------------
    // boot
    // -----------------------------------------------------------
    //
    // Puts the page links of every paginated list back on
    // Bootstrap 4 — the template Laravel 7 used by default and
    // the pages' styles expect. Laravel 8 made Tailwind the
    // default, and without this line the links of the news
    // list and the panel's lists would lose their look.
    //
    // Then registers the captcha rule of the sign-up and
    // forgot-password forms again, over anhskohbo/no-captcha's
    // own (its provider boots earlier). The package posts the
    // token to Google with no error handling, so while Google
    // cannot be reached — whenever the exit proxy is down —
    // the connection error escaped the validator and both
    // forms answered with the 500 page. Here the error is
    // logged and the reCAPTCHA counts as unsolved: the form
    // comes back with the same error an unsolved one gets.
    // The service is looked up at every check, so the tests'
    // double (solveCaptcha) still takes its place.
    //
    // Used by:
    //   - Laravel, once every provider is registered
    // -----------------------------------------------------------

    public function boot()
    {
        Paginator::useBootstrapFour();

        Validator::extend('captcha', function ($attribute, $value) {
            try {
                return app('captcha')->verifyResponse($value, request()->getClientIp());
            } catch (GuzzleException $exception) {
                report($exception);
                return false;
            }
        });
    }
}
