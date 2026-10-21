<?php

// -----------------------------------------------------------
//  [*] PageExists — 404 for an information page not there
//
//  Stands in front of the public information page and
//  turns a page id that names no row of bei_information
//  into a 404, so the page never runs on nothing.
//
//  Used by:
//    - Kernel::$routeMiddleware (app/Http/Kernel.php) — the
//      alias page.exists
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Information as Information;








// -----------------------------------------------------------
// PageExists
// -----------------------------------------------------------
//
// Guards the information page: an id that names no page is
// a 404. Unlike NewsExists and LMExists it does not check
// that the id is a number, and the slug it reads is never
// used.
//
// Used by:
//   - Kernel::$routeMiddleware — the alias page.exists
//   - routes/web_user.php — GET /{lang}/page/{slug}/{uid}
// -----------------------------------------------------------

class PageExists
{






    // -----------------------------------------------------------
    // handle
    // -----------------------------------------------------------
    //
    // Checks the uid of the URL: exactly one bei_information
    // row with that id; a 404 otherwise.
    //
    // Used by:
    //   - Laravel's router, on the route in the class banner
    // -----------------------------------------------------------

    public function handle($request, Closure $next)
    {
        $slug = $request->route('slug');
        $uid = $request->route('uid');

        if(Information::where(['id'=>$uid])->count() == 1) {

            return $next($request);

        } else {

            abort(404);

        }
    }
}
