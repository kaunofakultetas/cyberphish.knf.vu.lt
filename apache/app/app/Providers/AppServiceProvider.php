<?php

// -----------------------------------------------------------
//  [*] AppServiceProvider — the app's own provider
//
//  Laravel's place for the app's own container bindings
//  and boot-time wiring. The app binds nothing; at boot it
//  keeps the paginator on the Bootstrap 4 template the
//  pages were built with, makes the reCAPTCHA rule survive
//  an outage of Google, and hands a badge announcement that
//  waits in the session to the first page that shows
//  messages.
//
//  Used by:
//    - config/app.php — the providers list
// -----------------------------------------------------------


namespace App\Providers;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;








// -----------------------------------------------------------
// AppServiceProvider
// -----------------------------------------------------------
//
// Registers nothing; boots the paginator's template, the
// reCAPTCHA rule and the waiting badge announcement.
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
    // Last, parts/notif.blade.php — the messages of the member
    // pages that show any — gets badge_waiting: whether a
    // badge awarded on an earlier page that shows no messages
    // still waits to be announced to the member signed in now
    // (BaseController notes the member's id in
    // new_badge_waiting). The note is taken from the session
    // as the partial renders, so the announcement comes once,
    // on the first page that can show it; a note of another
    // member — signed out since, the session kept — goes
    // unannounced.
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

        View::composer('parts.notif', function ($view) {
            $waiting = Session::pull('new_badge_waiting');
            $view->with('badge_waiting', $waiting !== null && Auth::guard('mem')->check() && $waiting == Auth::guard('mem')->id());
        });
    }
}
