<?php

// -----------------------------------------------------------
//  [*] RedirectIfAuthenticated — the visitors-only check
//
//  Laravel's guest middleware, rewritten for the app's two
//  guards: a signed-in manager is sent to the admin
//  dashboard and a signed-in member to the home page,
//  instead of seeing a login page again.
//
//  Used by:
//    - Kernel::$routeMiddleware (app/Http/Kernel.php) — the
//      alias guest
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Log;
use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Support\Facades\Auth;








// -----------------------------------------------------------
// RedirectIfAuthenticated
// -----------------------------------------------------------
//
// Guards the pages meant for visitors — the two login
// forms, forgot-password, the reset link — by redirecting
// a user the named guard already has signed in: guest:man
// to the admin dashboard, guest:mem to the home page. It
// never refuses with an error.
//
// Used by:
//   - Kernel::$routeMiddleware — the alias guest
//   - Auth\LoginController — guest and guest:mem, on every
//     action but logout
//   - Auth\AdminLoginController — guest, guest:mem and
//     guest:man, on every action but logout
//   - Auth\RegisterController — a bare guest
// -----------------------------------------------------------

class RedirectIfAuthenticated
{






    // -----------------------------------------------------------
    // handle
    // -----------------------------------------------------------
    //
    // Checks the guard the alias names: man with a manager
    // signed in goes to the admin dashboard, mem with a member
    // signed in to the home page; everything else passes.
    //
    // Known bug (low): a bare guest names no guard and falls
    // through to the request (RedirectIfAuthenticated.php:90),
    // and RegisterController names none, so a signed-in
    // member can still open the registration form and post
    // it. Fix: guest:mem in RegisterController.
    // Pinned as register-while-signed-in.
    //
    // Used by:
    //   - Laravel's router, for the controllers in the class
    //     banner
    // -----------------------------------------------------------

    public function handle($request, Closure $next, $guard = null)
    {
        if ($guard =='man' && Auth::guard($guard)->check()) {

            return redirect(route("admin_dashboard"));

        } elseif ($guard =='mem' && Auth::guard($guard)->check()) {

            return redirect(route("home"));

        }

        return $next($request);
    }
}
