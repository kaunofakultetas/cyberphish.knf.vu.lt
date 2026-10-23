<?php

// -----------------------------------------------------------
//  [*] Perms — the main-admin check of the panel
//
//  Splits the admin panel between the two kinds of
//  manager: the pages behind this check are for main admins
//  only, and a country manager who opens one gets a 404, as
//  if the page did not exist.
//
//  Used by:
//    - Kernel::$routeMiddleware (app/Http/Kernel.php) — the
//      alias perms
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Closure;
use Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;








// -----------------------------------------------------------
// Perms
// -----------------------------------------------------------
//
// Guards the main admin's pages of the panel and refuses
// anyone else with a 404. It trusts the main flag of the
// session, which AdminLoginController::login copies from
// the manager's bei_managers row at sign-in and
// SignOutSwitchedOff copies again on every request, so a
// change to that row counts from the manager's next
// request on.
//
// Used by:
//   - Kernel::$routeMiddleware — the alias perms
//   - routes/web_admin.php — after auth.admin:man on every
//     panel route but the dashboard and the four member
//     reports a country manager may open
// -----------------------------------------------------------

class Perms
{






    // -----------------------------------------------------------
    // handle
    // -----------------------------------------------------------
    //
    // Checks the session's main flag: 1 passes; anything else
    // — a country manager's 0, or no flag at all — is a 404.
    //
    // Used by:
    //   - Laravel's router, on the routes in the class banner
    // -----------------------------------------------------------

    public function handle($request, Closure $next)
    {
        if(Session::get('main') == 1){
            return $next($request);
        } else {
            abort(404);
        }
    }
}
