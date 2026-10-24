<?php

// -----------------------------------------------------------
//  [*] RouteServiceProvider — loads the route files
//
//  Tells Laravel where the routes are: routes/web_user.php
//  and routes/web_admin.php under the web middleware group
//  with no prefix, routes/api.php under the api group with
//  the /api prefix. The order it loads them in decides
//  which route answers a URL that two of them match.
//
//  Used by:
//    - config/app.php — the providers list
// -----------------------------------------------------------


namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;








// -----------------------------------------------------------
// RouteServiceProvider
// -----------------------------------------------------------
//
// Laravel's route provider with the app's three files.
//
//   boot              — Laravel's, unchanged
//   map               — the three files, in order
//   mapWebUserRoutes  — routes/web_user.php
//   mapWebAdminRoutes — routes/web_admin.php
//   mapApiRoutes      — routes/api.php
//
// Used by:
//   - config/app.php — the providers list
//   - Auth\VerificationController, ResetPasswordController,
//     ConfirmPasswordController — read HOME; no route
//     reaches them
// -----------------------------------------------------------

class RouteServiceProvider extends ServiceProvider
{

    // The namespace the controller names of the route files
    // are resolved in
    protected $namespace = 'App\Http\Controllers';

    // Where Laravel's auth scaffolding sends a signed-in
    // user: the home page
    public const HOME = '/';






    // -----------------------------------------------------------
    // boot
    // -----------------------------------------------------------
    //
    // Holds every route's {lang} to two letters of either
    // case — a site code, or another pair, for which the page
    // keeps the session's language; the lookups of a language
    // by its locale ignore case, so an upper-case code still
    // finds its rows — then runs Laravel's own boot, empty in
    // Laravel 13. Any other segment once passed: api took the
    // site's pages under /api/, and a line break reached a
    // redirect's Location header, a 500. The routes load
    // through map from the booted callback that Laravel's
    // register method sets up, which runs right after this
    // boot.
    //
    // Used by:
    //   - Laravel, once every provider is registered
    // -----------------------------------------------------------

    public function boot()
    {
        Route::pattern('lang', '[A-Za-z]{2}');

        parent::boot();
    }






    // -----------------------------------------------------------
    // map
    // -----------------------------------------------------------
    //
    // Loads the three route files in this order: api.php,
    // web_admin.php, web_user.php. The order counts, since
    // Laravel answers a request with the first route that
    // matches it: web_user.php's {lang} took any value before
    // boot held it to two letters, and its {lang}/news routes
    // then answered the panel's news pages for a language
    // called admin-panel. Every admin route starts with
    // admin-panel/, so loaded first they can shadow no member
    // page.
    //
    // Used by:
    //   - Laravel's RouteServiceProvider::loadRoutes, from the
    //     booted callback its register sets up, right after
    //     this provider boots — the image never caches its
    //     routes
    // -----------------------------------------------------------

    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebAdminRoutes();

        $this->mapWebUserRoutes();
    }






    // -----------------------------------------------------------
    // mapWebUserRoutes
    // -----------------------------------------------------------
    //
    // Loads routes/web_user.php — the public pages and the
    // member side — under the web group with no prefix, its
    // controller names resolved in the namespace above.
    //
    // Used by:
    //   - map (above)
    // -----------------------------------------------------------

    protected function mapWebUserRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web_user.php'));
    }






    // -----------------------------------------------------------
    // mapWebAdminRoutes
    // -----------------------------------------------------------
    //
    // Loads routes/web_admin.php — the admin panel — under
    // the web group with no prefix of its own: its paths
    // spell out admin-panel themselves.
    //
    // Used by:
    //   - map (above)
    // -----------------------------------------------------------

    protected function mapWebAdminRoutes()
    {
        Route::middleware('web')
        ->namespace($this->namespace)
        ->group(base_path('routes/web_admin.php'));
    }






    // -----------------------------------------------------------
    // mapApiRoutes
    // -----------------------------------------------------------
    //
    // Loads routes/api.php under the api group, every path
    // prefixed with api; the file declares no routes.
    //
    // Used by:
    //   - map (above)
    // -----------------------------------------------------------

    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }
}
