<?php

// -----------------------------------------------------------
//  [*] AppServiceProvider — the app's own provider, empty
//
//  Laravel's place for the app's own container bindings
//  and boot-time wiring. The app has none: both hooks are
//  empty.
//
//  Used by:
//    - config/app.php — the providers list
// -----------------------------------------------------------


namespace App\Providers;

use Illuminate\Support\ServiceProvider;








// -----------------------------------------------------------
// AppServiceProvider
// -----------------------------------------------------------
//
// Registers nothing and boots nothing.
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
    // Wires nothing once the providers are registered.
    //
    // Used by:
    //   - Laravel, once every provider is registered
    // -----------------------------------------------------------

    public function boot()
    {
    }
}
