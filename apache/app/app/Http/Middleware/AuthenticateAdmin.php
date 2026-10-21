<?php

// -----------------------------------------------------------
//  [*] AuthenticateAdmin — the sign-in check of the panel
//
//  Laravel's Authenticate middleware once more, under an
//  alias of its own, so that a visitor turned away from the
//  admin panel lands on the admin login page rather than
//  the member one. The panel's routes name it with the man
//  guard.
//
//  Used by:
//    - Kernel::$routeMiddleware (app/Http/Kernel.php) — the
//      alias auth.admin
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;








// -----------------------------------------------------------
// AuthenticateAdmin
// -----------------------------------------------------------
//
// Guards the admin panel: a request passes when the guard
// the route names — man — has a manager signed in.
// Otherwise Laravel redirects a browser to the admin login
// page, remembering the URL it asked for so the login can
// return there, and answers a JSON request with 401.
//
// Used by:
//   - Kernel::$routeMiddleware — the alias auth.admin
//   - routes/web_admin.php — auth.admin:man on every route
//     but the login form, its post and the logout
// -----------------------------------------------------------

class AuthenticateAdmin extends Middleware
{






    // -----------------------------------------------------------
    // redirectTo
    // -----------------------------------------------------------
    //
    // Where a refused request goes: the admin login page (the
    // route named admin_login, /admin-panel) for a browser,
    // nowhere for a JSON request, which Laravel then answers
    // with 401.
    //
    // Used by:
    //   - Laravel's Authenticate middleware, when it refuses a
    //     request
    // -----------------------------------------------------------

    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('admin_login');
        }
    }
}
