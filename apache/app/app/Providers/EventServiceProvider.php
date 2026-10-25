<?php

// -----------------------------------------------------------
//  [*] EventServiceProvider — the event listeners
//
//  Laravel's map of events to listeners: the scaffold's
//  Registered event, heard by
//  SendEmailVerificationNotification, and Laravel's Login
//  event, which marks the new session with the password it
//  signed in with (SignOutChangedPassword). Nothing fires
//  Registered — RegisterController's own register replaces
//  the trait method that would — so the verification mail
//  the app sends is its own SendVerification.
//
//  Used by:
//    - config/app.php — the providers list
// -----------------------------------------------------------


namespace App\Providers;

use App\Http\Middleware\SignOutChangedPassword;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;








// -----------------------------------------------------------
// EventServiceProvider
// -----------------------------------------------------------
//
// One mapping, which nothing sets off, and a boot that
// listens for every sign-in.
//
// Used by:
//   - config/app.php — the providers list
// -----------------------------------------------------------

class EventServiceProvider extends ServiceProvider
{

    // The events and the listeners each one wakes
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];






    // -----------------------------------------------------------
    // boot
    // -----------------------------------------------------------
    //
    // Laravel's own boot, empty in Laravel 13, then a listener
    // for every sign-in of either login: the session is marked
    // with the hash the login checked the password against, so
    // that a new password set anywhere — even while the check
    // ran — ends it at its next request
    // (SignOutChangedPassword). A hash of an older cost, which
    // the login has just rewritten, gives way to the row's.
    // The mapping above is registered by the booting callback
    // that Laravel's register method sets up, which runs just
    // before this boot.
    //
    // Used by:
    //   - Laravel, once every provider is registered
    // -----------------------------------------------------------

    public function boot()
    {
        parent::boot();

        Event::listen(Login::class, function (Login $event) {
            $checked = $event->user->getAuthPassword();

            SignOutChangedPassword::mark($event->guard, $event->user->getAuthIdentifier(), Hash::needsRehash($checked) ? null : $checked);
        });
    }
}
