<?php

// -----------------------------------------------------------
//  [*] EventServiceProvider — the event listeners
//
//  Laravel's map of events to listeners, as the scaffold
//  left it: Laravel's Registered event, heard by
//  SendEmailVerificationNotification. Nothing fires
//  Registered — RegisterController's own register replaces
//  the trait method that would — so the verification mail
//  the app sends is its own SendVerification.
//
//  Used by:
//    - config/app.php — the providers list
// -----------------------------------------------------------


namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;








// -----------------------------------------------------------
// EventServiceProvider
// -----------------------------------------------------------
//
// One mapping, which nothing sets off, and Laravel's boot.
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
    // Laravel's own boot, which registers the mapping above;
    // nothing added.
    //
    // Used by:
    //   - Laravel, once every provider is registered
    // -----------------------------------------------------------

    public function boot()
    {
        parent::boot();
    }
}
