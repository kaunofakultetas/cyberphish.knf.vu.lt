<?php

// -----------------------------------------------------------
//  [*] AppServiceProvider — the app's own provider
//
//  Laravel's place for the app's own container bindings
//  and boot-time wiring. The app binds nothing; at boot it
//  only keeps the paginator on the Bootstrap 4 template the
//  pages were built with.
//
//  Used by:
//    - config/app.php — the providers list
// -----------------------------------------------------------


namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;








// -----------------------------------------------------------
// AppServiceProvider
// -----------------------------------------------------------
//
// Registers nothing; boots the paginator's template.
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
    // Used by:
    //   - Laravel, once every provider is registered
    // -----------------------------------------------------------

    public function boot()
    {
        Paginator::useBootstrapFour();
    }
}
